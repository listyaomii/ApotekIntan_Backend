<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use App\Models\Pasien;
use Illuminate\Http\Request;

class PenjualanController extends Controller
{
    public function index()
    {
        $penjualan = Penjualan::select('penjualan.*', 'pasien.nama as nama_pasien')
            ->leftJoin('pasien', 'penjualan.id_pasien', '=', 'pasien.id_pasien')
            ->orderBy('penjualan.id_penjualan', 'desc')
            ->paginate(5);

        return response()->json($penjualan);
    }

    public function indexLaporan()
    {
        $penjualan = Penjualan::select('penjualan.*', 'pasien.nama as nama_pasien')
            ->leftJoin('pasien', 'penjualan.id_pasien', '=', 'pasien.id_pasien')
            ->orderBy('penjualan.id_penjualan', 'desc')
            ->with(['detailPenjualan.obat:id_obat,nama_obat'])
            ->paginate(10);

        return response()->json($penjualan);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tgl_jual'            => 'required|date',
            'total'               => 'required|numeric|min:0',
            'id_pegawai'          => 'required|exists:pegawai,id_pegawai',
            'id_pasien'           => 'nullable|exists:pasien,id_pasien',
            'detail'              => 'required|array|min:1',
            'detail.*.id_obat'    => 'required|exists:obat,id_obat',
            'detail.*.jumlah'     => 'required|integer|min:1',
            'detail.*.harga_satuan' => 'required|numeric|min:0',
            'detail.*.subtotal'   => 'required|numeric|min:0',
        ]);

        // Buat penjualan
        $penjualan = Penjualan::create([
            'tgl_jual'   => $request->tgl_jual,
            'total'      => $request->total,
            'id_pegawai' => $request->id_pegawai,
            'id_pasien'  => $request->id_pasien ?? null,
        ]);

        // Buat semua detail penjualan
        foreach ($request->detail as $item) {
            // Ambil harga_beli PRODUK SAAT INI dan simpan sebagai snapshot histori,
            // supaya laporan laba di masa depan tidak berubah walau harga_beli produk
            // di tabel `obat` nanti diupdate lagi (misal karena ada pembelian baru).
            $obat = \App\Models\Obat::find($item['id_obat']);

            DetailPenjualan::create([
                'id_penjualan' => $penjualan->id_penjualan,
                'id_obat'      => $item['id_obat'],
                'jumlah'       => $item['jumlah'],
                'harga_satuan' => $item['harga_satuan'],
                'harga_beli'   => $obat->harga_beli ?? 0, // snapshot HPP saat transaksi ini terjadi
                'subtotal'     => $item['subtotal'],
            ]);
        }

        return response()->json([
            'message' => 'Transaksi penjualan berhasil disimpan',
            'data'    => $penjualan->load('detailPenjualan'),
        ], 201);
    }

    public function show($id)
    {
        return response()->json(Penjualan::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $item = Penjualan::findOrFail($id);
        $item->update($request->only((new Penjualan())->getFillable()));

        return response()->json($item);
    }

    public function destroy($id)
    {
        Penjualan::findOrFail($id)->delete();

        return response()->json(null, 204);
    }

    public function count()
    {
        return response()->json(['total' => Penjualan::count()]);
    }

    public function pendapatan()
    {
        return response()->json(['total' => Penjualan::sum('total')]);
    }

    public function grafikMingguan()
    {
        $data = Penjualan::selectRaw('DATE(tgl_jual) as tanggal, SUM(total) as total')
            ->whereBetween('tgl_jual', [now()->startOfWeek(), now()->endOfWeek()])
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        return response()->json($data);
    }

    public function grafikPenjualan(Request $request)
    {
        $periode = $request->query('periode', 'harian');
        $startDate = $request->query('start_date', now()->subDays(7)->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        $query = Penjualan::whereBetween('tgl_jual', [$startDate, $endDate]);

        if ($periode === 'harian') {
            $data = $query->selectRaw('DATE(tgl_jual) as label, SUM(total) as total')
                ->groupBy('label')
                ->orderBy('label')
                ->get();
        } elseif ($periode === 'mingguan') {
            $data = $query->selectRaw('YEARWEEK(tgl_jual, 1) as week_key, MIN(DATE(tgl_jual)) as label, SUM(total) as total')
                ->groupBy('week_key')
                ->orderBy('week_key')
                ->get();
        } elseif ($periode === 'bulanan') {
            $data = $query->selectRaw('DATE_FORMAT(tgl_jual, "%Y-%m") as label, SUM(total) as total')
                ->groupBy('label')
                ->orderBy('label')
                ->get();
        }

        return response()->json($data);
    }


    public function grafikLaba(Request $request)
    {
        $periode   = $request->query('periode', 'harian');
        $startDate = $request->query('start_date', now()->subDays(13)->toDateString());
        $endDate   = $request->query('end_date', now()->toDateString());

        // ---- SUMBER 1: Transaksi kasir (Penjualan) ----
        // Modal pakai snapshot harga_beli yang tersimpan di detail_penjualan (akurat),
        // fallback ke harga_beli produk terkini kalau NULL (transaksi lama).
        $dariPenjualan = \DB::table('detail_penjualan')
            ->join('penjualan', 'detail_penjualan.id_penjualan', '=', 'penjualan.id_penjualan')
            ->join('obat', 'detail_penjualan.id_obat', '=', 'obat.id_obat')
            ->whereBetween('penjualan.tgl_jual', [$startDate, $endDate])
            ->selectRaw('
                DATE(penjualan.tgl_jual) as tanggal,
                detail_penjualan.subtotal as subtotal,
                (COALESCE(detail_penjualan.harga_beli, obat.harga_beli) * detail_penjualan.jumlah) as modal
            ')
            ->get();

        // ---- SUMBER 2: Pesan Antar (Flutter) yang statusnya "selesai" ----
        // CATATAN: detail_pesanan tidak punya snapshot harga_beli, jadi modal di sini
        // pakai obat.harga_beli TERKINI (bukan historis). Ini keterbatasan yang perlu
        // diketahui: kalau harga beli produk berubah setelah pesanan selesai, modal
        // untuk pesanan lama bisa jadi kurang akurat dibanding sumber Penjualan di atas.
        $dariPesanAntar = \DB::table('detail_pesanan')
            ->join('pesan_antar', 'detail_pesanan.id_pesanan', '=', 'pesan_antar.id_pesanan')
            ->join('obat', 'detail_pesanan.id_obat', '=', 'obat.id_obat')
            ->where('pesan_antar.status', 'selesai')
            ->whereBetween('pesan_antar.tgl_pesan', [$startDate, $endDate])
            ->selectRaw('
                DATE(pesan_antar.tgl_pesan) as tanggal,
                detail_pesanan.subtotal as subtotal,
                (obat.harga_beli * detail_pesanan.jumlah) as modal
            ')
            ->get();

        // Gabungkan kedua sumber jadi satu koleksi baris per-item
        $gabungan = $dariPenjualan->concat($dariPesanAntar);

        if ($gabungan->isEmpty()) {
            return response()->json([]);
        }

        // Tentukan "kunci pengelompokan" sesuai periode yang diminta
        $groupKeyFn = function ($row) use ($periode) {
            $tanggal = \Carbon\Carbon::parse($row->tanggal);
            return match ($periode) {
                'mingguan' => $tanggal->format('oW'), // tahun ISO + minggu ISO, mirip YEARWEEK(...,1)
                'bulanan'  => $tanggal->format('Y-m'),
                default    => $tanggal->format('Y-m-d'), // harian
            };
        };

        // Simpan juga label tanggal representatif untuk tiap grup
        $labelFn = function ($row) use ($periode) {
            $tanggal = \Carbon\Carbon::parse($row->tanggal);
            return $periode === 'bulanan' ? $tanggal->format('Y-m') : $tanggal->format('Y-m-d');
        };

        $grouped = $gabungan->groupBy($groupKeyFn);

        $data = $grouped->map(function ($rows) use ($labelFn) {
            $totalPenjualan = $rows->sum('subtotal');
            $totalModal     = $rows->sum('modal');

            // Untuk periode mingguan, label pakai tanggal PALING AWAL dalam grup itu
            // (mirip MIN(DATE(...)) di query SQL sebelumnya)
            $labelTerawal = $rows->sortBy('tanggal')->first();

            return [
                'label'           => $labelFn($labelTerawal),
                'total_penjualan' => (float) $totalPenjualan,
                'total_modal'     => (float) $totalModal,
                'total_laba'      => (float) ($totalPenjualan - $totalModal),
            ];
        })
        ->values() // reset key supaya jadi array json biasa, bukan object dengan key aneh
        ->sortBy('label')
        ->values();

        return response()->json($data);
    }

    public function pendapatanBulanIni()
    {
        $total = Penjualan::whereMonth('tgl_jual', now()->month)
                        ->whereYear('tgl_jual', now()->year)
                        ->sum('total');

        return response()->json(['total' => $total]);
    }

    public function countBulanIni()
    {
        $total = Penjualan::whereMonth('tgl_jual', now()->month)
                        ->whereYear('tgl_jual', now()->year)
                        ->count();

        return response()->json(['total' => $total]);
    }
}
