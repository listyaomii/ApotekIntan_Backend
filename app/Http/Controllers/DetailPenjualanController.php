<?php

namespace App\Http\Controllers;

use App\Models\DetailPenjualan;
use Illuminate\Http\Request;

class DetailPenjualanController extends Controller
{
    public function index()
    {
        return response()->json(DetailPenjualan::all());
    }

    public function store(Request $request)
    {
        $data = $request->only((new DetailPenjualan())->getFillable());
        $item = DetailPenjualan::create($data);

        return response()->json($item, 201);
    }

    public function show($id)
    {
        return response()->json(DetailPenjualan::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $item = DetailPenjualan::findOrFail($id);
        $item->update($request->only((new DetailPenjualan())->getFillable()));

        return response()->json($item);
    }

    public function destroy($id)
    {
        DetailPenjualan::findOrFail($id)->delete();

        return response()->json(null, 204);
    }

    public function terlaris()
    {
        $data = DetailPenjualan::selectRaw('id_obat, SUM(jumlah) as total_terjual')
            ->with('obat:id_obat,nama_obat')
            ->groupBy('id_obat')
            ->orderByDesc('total_terjual')
            ->limit(5)
            ->get();

        return response()->json($data);
    }


    public function terlarisByPeriode(Request $request)
    {
        $periode   = $request->query('periode', 'harian');
        $startDate = $request->query('start_date', now()->toDateString());
        $endDate   = $request->query('end_date', now()->toDateString());

        $data = \DB::table('detail_penjualan')
            ->join('penjualan', 'detail_penjualan.id_penjualan', '=', 'penjualan.id_penjualan')
            ->join('obat', 'detail_penjualan.id_obat', '=', 'obat.id_obat')
            ->whereBetween('penjualan.tgl_jual', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw('
                obat.id_obat,
                obat.nama_obat,
                obat.stok,
                obat.stok_minimum,
                SUM(detail_penjualan.jumlah) as total_terjual,
                SUM(detail_penjualan.subtotal) as total_omzet
            ')
            ->groupBy('obat.id_obat', 'obat.nama_obat', 'obat.stok', 'obat.stok_minimum')
            ->orderByDesc('total_terjual')
            ->limit(10)
            ->get();

        return response()->json($data);
    }
}
