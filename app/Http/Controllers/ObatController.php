<?php

namespace App\Http\Controllers;

use App\Models\Obat;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ObatController extends Controller
{
    public function index(Request $request)
    {
        $query = Obat::query();

        // Search by name: ?q=term or ?search=term
        $q = $request->query('q', $request->query('search'));
        if ($q) {
            $query->where('nama_obat', 'like', "%{$q}%");
        }

        // Filter by kategori and satuan
        if ($kategori = $request->query('id_kategori')) {
            $query->where('id_kategori', $kategori);
        }
        if ($satuan = $request->query('id_satuan')) {
            $query->where('id_satuan', $satuan);
        }

        // Stock filters: stok_status=out|low
        if ($stokStatus = $request->query('stok_status')) {
            if ($stokStatus === 'out') {
                $query->where('stok', '<=', 0);
            } elseif ($stokStatus === 'low') {
                $query->whereColumn('stok', '<=', 'stok_minimum');
            }
        }

        // Expiration filters: expired=true, expired=near (with days param)
        if ($expired = $request->query('expired')) {
            if (in_array($expired, ['1', 'true', 'yes'], true)) {
                $query->whereDate('tgl_kadaluarsa', '<', Carbon::today());
            } elseif ($expired === 'near') {
                $days = (int) $request->query('days', 30);
                $query->whereBetween('tgl_kadaluarsa', [Carbon::today(), Carbon::today()->addDays($days)]);
            }
        }

        // Sorting: sort_by and sort_dir
        if ($sortBy = $request->query('sort_by')) {
            $dir = strtolower($request->query('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
            $allowed = ['nama_obat', 'harga_jual', 'harga_beli', 'stok'];
            if (in_array($sortBy, $allowed, true)) {
                $query->orderBy($sortBy, $dir);
            }
        }

        // Pagination support: ?paginate=true&per_page=20
        if ($request->query('paginate') === 'true' || $request->has('per_page')) {
            $perPage = (int) $request->query('per_page', 10);
            return response()->json($query->paginate(max(1, $perPage)));
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_obat' => 'required|string|max:255|unique:obat,nama_obat',
        ]);

        $data = $request->only((new Obat())->getFillable());
        $item = Obat::create($data);

        return response()->json($item, 201);
    }

    public function show($id)
    {
        return response()->json(Obat::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $item = Obat::findOrFail($id);

        $validated = $request->validate([
            'nama_obat' => 'sometimes|required|string|max:255|unique:obat,nama_obat,' . $id . ',id_obat',
        ]);

        $item->update($request->only((new Obat())->getFillable()));

        return response()->json($item);
    }

    public function destroy($id)
    {
        Obat::findOrFail($id)->delete();

        return response()->json(null, 204);
    }

    // Convenience endpoints
    public function outOfStock()
    {
        $data = Obat::where('stok', '<=', 0)->get();
        return response()->json($data);
    }

    public function nearlyExpired(Request $request)
    {
        $days = (int) $request->query('days', 30);
        $data = Obat::whereBetween('tgl_kadaluarsa', [Carbon::today(), Carbon::today()->addDays($days)])->get();
        return response()->json($data);
    }

    public function expired()
    {
        $data = Obat::whereDate('tgl_kadaluarsa', '<', Carbon::today())->get();
        return response()->json($data);
    }

    public function count()
    {
        return response()->json(['total' => Obat::count()]);
    }

    public function stokHabis()
    {
        $count = Obat::whereColumn('stok', '<=', 'stok_minimum')->count();
        return response()->json(['total' => $count]);
    }

    public function laporanStok()
    {
        $obat = Obat::with('kategori')
            ->select(
                'id_obat',
                'nama_obat',
                'stok',
                'stok_minimum',
                'tgl_kadaluarsa',
                'id_kategori'  // wajib ada meski hidden, untuk relasi
            )
            ->orderBy('nama_obat')
            ->paginate(10);

        // Transform agar nama_kategori serta status expired/low stock muncul di response
        $obat->getCollection()->transform(function ($item) {
            $isExpired  = $item->tgl_kadaluarsa && Carbon::parse($item->tgl_kadaluarsa)->lt(Carbon::today());
            $isLowStock = $item->stok <= $item->stok_minimum;

            return [
                'id_obat'        => $item->id_obat,
                'nama_obat'      => $item->nama_obat,
                'nama_kategori'  => $item->kategori?->nama_kategori ?? '-',
                'stok'           => $item->stok,
                'stok_minimum'   => $item->stok_minimum,
                'tgl_kadaluarsa' => $item->tgl_kadaluarsa,
                'is_expired'     => $isExpired,
                'is_low_stock'   => $isLowStock,
            ];
        });

        return response()->json($obat);
    }

    public function statusStok()
    {
        $data = Obat::where(function ($query) {
                $query->whereDate('tgl_kadaluarsa', '<', Carbon::today())
                    ->orWhereColumn('stok', '<=', 'stok_minimum');
            })
            ->get()
            ->map(function ($item) {
                $isExpired  = $item->tgl_kadaluarsa && Carbon::parse($item->tgl_kadaluarsa)->lt(Carbon::today());
                $isLowStock = $item->stok <= $item->stok_minimum;

                $item->is_expired   = $isExpired;
                $item->is_low_stock = $isLowStock;

                return $item;
            });

        return response()->json($data);
    }

    public function uploadGambar(Request $request, $id)
    {
        $request->validate([
            'gambar' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $obat = Obat::findOrFail($id);

        // Hapus gambar lama kalau ada
        if ($obat->gambar && Storage::disk('public')->exists($obat->gambar)) {
            Storage::disk('public')->delete($obat->gambar);
        }

        // Simpan gambar baru
        $path = $request->file('gambar')->store('obat', 'public');
        $obat->update(['gambar' => $path]);

        return response()->json([
            'message'    => 'Gambar berhasil diupload',
            'gambar'     => $obat->gambar,
            'gambar_url' => $obat->gambar_url,
        ]);
    }
}
