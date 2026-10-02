<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\Request;

class KategoriController extends Controller
{
    public function index()
    {
        return response()->json(Kategori::all());
    }

    public function store(Request $request)
    {
        $data = $request->only((new Kategori())->getFillable());
        $item = Kategori::create($data);

        return response()->json($item, 201);
    }

    public function show($id)
    {
        return response()->json(Kategori::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $item = Kategori::findOrFail($id);
        $item->update($request->only((new Kategori())->getFillable()));

        return response()->json($item);
    }

    public function destroy($id)
    {
        Kategori::findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
