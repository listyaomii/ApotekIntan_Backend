<?php

namespace App\Http\Controllers;

use App\Models\Pegawai;
use Illuminate\Http\Request;

class PegawaiController extends Controller
{
    public function index()
    {
        return response()->json(Pegawai::all());
    }

    public function store(Request $request)
    {
        $data = $request->only((new Pegawai())->getFillable());
        $item = Pegawai::create($data);

        return response()->json($item, 201);
    }

    public function show($id)
    {
        return response()->json(Pegawai::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $item = Pegawai::findOrFail($id);
        $item->update($request->only((new Pegawai())->getFillable()));

        return response()->json($item);
    }

    public function destroy($id)
    {
        Pegawai::findOrFail($id)->delete();

        return response()->json(null, 204);
    }

    public function admin()
    {
        return response()->json(
            Pegawai::where('role', 'admin')->get()
        );
    }

    public function kurir()
    {
        return response()->json(
            Pegawai::where('role', 'kurir')->get()
        );
    }

    public function storeKurir(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'email' => 'required|email|unique:pegawai,email',
            'pin' => 'required|string|min:4',
        ]);

        $kurir = Pegawai::create([
            'nama' => $request->nama,
            'email' => $request->email,
            'pin' => md5($request->pin),
            'role' => 'kurir',
        ]);

        return response()->json([
            'message' => 'Kurir berhasil ditambahkan',
            'data' => $kurir
        ], 201);
    }

    public function storeAdmin(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'email' => 'required|email|unique:pegawai,email',
            'pin' => 'required|string|min:4',
        ]);

        $admin = Pegawai::create([
            'nama' => $request->nama,
            'email' => $request->email,
            'pin' => md5($request->pin),
            'role' => 'admin',
        ]);

        return response()->json([
            'message' => 'Admin berhasil ditambahkan',
            'data' => $admin
        ], 201);
    }
}
