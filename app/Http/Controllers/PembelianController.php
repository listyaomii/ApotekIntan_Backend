<?php

namespace App\Http\Controllers;

use App\Models\Pembelian;
use App\Models\DetailPembelian;
use App\Models\Obat;
use App\Models\Supplier;
use Illuminate\Http\Request;

class PembelianController extends Controller
{
    
    public function index()
    {
        $pembelian = Pembelian::select(
                'pembelian.*',
                'supplier.nama_supplier'
            )
            ->join('supplier', 'pembelian.id_supplier', '=', 'supplier.id_supplier')
            ->orderBy('pembelian.id_pembelian', 'desc')
            ->paginate(5);

        return response()->json($pembelian);
    }

    public function indexLaporan()
    {
        $pembelian = Pembelian::select('pembelian.*', 'supplier.nama_supplier')
            ->join('supplier', 'pembelian.id_supplier', '=', 'supplier.id_supplier')
            ->orderBy('pembelian.id_pembelian', 'desc')
            ->with(['detailPembelian.obat:id_obat,nama_obat'])
            ->paginate(10);

        return response()->json($pembelian);
    }

    public function store(Request $request)
    {
        $request->validate([
            'tgl_beli' => 'required|date',
            'id_supplier' => 'required|exists:supplier,id_supplier',
            'id_pegawai' => 'required',
            'detail' => 'required|array|min:1',

            'detail.*.id_obat' => 'required|exists:obat,id_obat',
            'detail.*.jumlah' => 'required|integer|min:1',
            'detail.*.harga_satuan' => 'required|numeric|min:0',
            'detail.*.harga_jual' => 'required|numeric|min:0',
            'detail.*.id_kategori' => 'required|exists:kategori,id_kategori',
            'detail.*.id_satuan' => 'required|exists:satuan,id_satuan',
            'detail.*.tgl_kadaluarsa' => 'required|date',
        ]);

        $pembelian = Pembelian::create([
            'tgl_beli' => $request->tgl_beli,
            'total' => 0,
            'keterangan' => $request->keterangan,
            'id_supplier' => $request->id_supplier,
            'id_pegawai' => $request->id_pegawai,
        ]);

        $total = 0;

        foreach ($request->detail as $detail) {

            // Hitung subtotal
            $subtotal = $detail['jumlah'] * $detail['harga_satuan'];

            // Simpan detail pembelian
            DetailPembelian::create([
                'jumlah' => $detail['jumlah'],
                'harga_satuan' => $detail['harga_satuan'],
                'subtotal' => $subtotal,
                'id_pembelian' => $pembelian->id_pembelian,
                'id_obat' => $detail['id_obat'],
            ]);

            $total += $subtotal;

            // Cari obat
            $obat = Obat::find($detail['id_obat']);

            if ($obat) {

                // Tambahkan stok
                $obat->stok += $detail['jumlah'];

                // Update harga beli
                $obat->harga_beli = $detail['harga_satuan'];

                // Update harga jual
                $obat->harga_jual = $detail['harga_jual'];

                // Update kategori
                $obat->id_kategori = $detail['id_kategori'];

                // Update satuan
                $obat->id_satuan = $detail['id_satuan'];

                // Update tanggal kadaluarsa
                $obat->tgl_kadaluarsa = $detail['tgl_kadaluarsa'];

                $obat->save();
            }
        }

        // Update total pembelian
        $pembelian->total = $total;
        $pembelian->save();

        return response()->json([
            'message' => 'Pembelian berhasil dibuat',
            'data' => $pembelian
        ], 201);
    }

    public function show($id)
    {
        return response()->json(Pembelian::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $item = Pembelian::findOrFail($id);
        $item->update($request->only((new Pembelian())->getFillable()));

        return response()->json($item);
    }

    public function destroy($id)
    {
        Pembelian::findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
