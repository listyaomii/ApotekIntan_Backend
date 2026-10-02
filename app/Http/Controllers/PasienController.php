<?php

namespace App\Http\Controllers;

use App\Models\Pasien;
use Illuminate\Http\Request;

class PasienController extends Controller
{
    public function index()
    {
        return response()->json(Pasien::all());
    }

    public function store(Request $request)
    {
        $data = $request->only((new Pasien())->getFillable());
        $item = Pasien::create($data);

        return response()->json($item, 201);
    }

    public function show($id)
    {
        return response()->json(Pasien::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $item = Pasien::findOrFail($id);
        $item->update($request->only((new Pasien())->getFillable()));

        return response()->json($item);
    }

    public function destroy($id)
    {
        Pasien::findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
