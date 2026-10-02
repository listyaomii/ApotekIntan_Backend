<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    public function index()
    {
        return response()->json(Notifikasi::all());
    }

    public function store(Request $request)
    {
        $data = $request->only((new Notifikasi())->getFillable());
        $item = Notifikasi::create($data);

        return response()->json($item, 201);
    }

    public function show($id)
    {
        return response()->json(Notifikasi::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $item = Notifikasi::findOrFail($id);
        $item->update($request->only((new Notifikasi())->getFillable()));

        return response()->json($item);
    }

    public function destroy($id)
    {
        Notifikasi::findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
