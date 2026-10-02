<?php

namespace App\Http\Controllers;

use App\Models\Satuan;
use Illuminate\Http\Request;

class SatuanController extends Controller
{
    public function index()
    {
        return response()->json(Satuan::all());
    }

    public function store(Request $request)
    {
        $data = $request->only((new Satuan())->getFillable());
        $item = Satuan::create($data);

        return response()->json($item, 201);
    }

    public function show($id)
    {
        return response()->json(Satuan::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $item = Satuan::findOrFail($id);
        $item->update($request->only((new Satuan())->getFillable()));

        return response()->json($item);
    }

    public function destroy($id)
    {
        Satuan::findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
