<?php

namespace App\Http\Controllers;

use App\Models\DetailPembelian;
use App\Models\DetailPenjualan;
use App\Models\Obat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MutasiStokController extends Controller
{
    public function index(Request $request)
    {
        $tipe = $request->query('tipe');
        $page = $request->query('page', 1);

        $masuk = DB::table('detail_pembelian')
            ->join('pembelian', 'detail_pembelian.id_pembelian', '=', 'pembelian.id_pembelian')
            ->join('obat', 'detail_pembelian.id_obat', '=', 'obat.id_obat')
            ->join('supplier', 'pembelian.id_supplier', '=', 'supplier.id_supplier')
            ->select(
                'detail_pembelian.id_detail_beli as id',
                'pembelian.tgl_beli as tanggal',
                'obat.nama_obat as produk',
                DB::raw("'Masuk' as tipe"),
                'detail_pembelian.jumlah',
                DB::raw("supplier.nama_supplier as pihak")
            );

        $keluar = DB::table('detail_penjualan')
            ->join('penjualan', 'detail_penjualan.id_penjualan', '=', 'penjualan.id_penjualan')
            ->join('obat', 'detail_penjualan.id_obat', '=', 'obat.id_obat')
            ->leftJoin('pasien', 'penjualan.id_pasien', '=', 'pasien.id_pasien')
            ->select(
                'detail_penjualan.id_detail_jual as id',
                'penjualan.tgl_jual as tanggal',
                'obat.nama_obat as produk',
                DB::raw("'Keluar' as tipe"),
                'detail_penjualan.jumlah',
                DB::raw("COALESCE(pasien.nama, 'Pasien Umum') as pihak")
            );

        if ($tipe === 'Masuk') {
            $result = $masuk->orderBy('tanggal', 'desc')->paginate(10);
        } elseif ($tipe === 'Keluar') {
            $result = $keluar->orderBy('tanggal', 'desc')->paginate(10);
        } else {
            $result = $masuk->unionAll($keluar)
                ->orderBy('tanggal', 'desc')
                ->paginate(4);
        }

        return response()->json($result);
    }

    public function masuk(Request $request)
    {
        $result = DB::table('detail_pembelian')
            ->join('pembelian', 'detail_pembelian.id_pembelian', '=', 'pembelian.id_pembelian')
            ->join('obat', 'detail_pembelian.id_obat', '=', 'obat.id_obat')
            ->join('supplier', 'pembelian.id_supplier', '=', 'supplier.id_supplier')
            ->select(
                'detail_pembelian.id_detail_beli as id',
                'pembelian.tgl_beli as tanggal',
                'obat.nama_obat as produk',
                'detail_pembelian.jumlah',
                'supplier.nama_supplier as pihak'
            )
            ->orderBy('pembelian.tgl_beli', 'desc')
            ->paginate(4);

        return response()->json($result);
    }

    public function keluar(Request $request)
    {
        $result = DB::table('detail_penjualan')
            ->join('penjualan', 'detail_penjualan.id_penjualan', '=', 'penjualan.id_penjualan')
            ->join('obat', 'detail_penjualan.id_obat', '=', 'obat.id_obat')
            ->leftJoin('pasien', 'penjualan.id_pasien', '=', 'pasien.id_pasien')
            ->select(
                'detail_penjualan.id_detail_jual as id',
                'penjualan.tgl_jual as tanggal',
                'obat.nama_obat as produk',
                'detail_penjualan.jumlah',
                DB::raw("COALESCE(pasien.nama, 'Pasien Umum') as pihak")
            )
            ->orderBy('penjualan.tgl_jual', 'desc')
            ->paginate(4);

        return response()->json($result);
    }
}