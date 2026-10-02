<?php

namespace App\Http\Controllers;

use App\Models\DetailPembelian;
use Illuminate\Http\Request;

class DetailPembelianController extends Controller
{
    public function index()
    {
        return response()->json(DetailPembelian::all());
    }

    public function store(Request $request)
    {
        $data = $request->only((new DetailPembelian())->getFillable());
        $item = DetailPembelian::create($data);

        return response()->json($item, 201);
    }

    public function show($id)
    {
        return response()->json(DetailPembelian::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $item = DetailPembelian::findOrFail($id);
        $item->update($request->only((new DetailPembelian())->getFillable()));

        return response()->json($item);
    }

    public function destroy($id)
    {
        DetailPembelian::findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
