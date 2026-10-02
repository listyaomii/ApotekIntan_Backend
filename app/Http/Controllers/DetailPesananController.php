<?php

namespace App\Http\Controllers;

use App\Models\DetailPesanan;
use Illuminate\Http\Request;

class DetailPesananController extends Controller
{
    public function index()
    {
        return response()->json(DetailPesanan::all());
    }

    public function store(Request $request)
    {
        $data = $request->only((new DetailPesanan())->getFillable());
        $item = DetailPesanan::create($data);

        return response()->json($item, 201);
    }

    public function show($id)
    {
        return response()->json(DetailPesanan::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $item = DetailPesanan::findOrFail($id);
        $item->update($request->only((new DetailPesanan())->getFillable()));

        return response()->json($item);
    }

    public function destroy($id)
    {
        DetailPesanan::findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
