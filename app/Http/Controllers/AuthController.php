<?php

namespace App\Http\Controllers;

use App\Models\Pasien;
use App\Models\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function staffLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'pin' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $pegawai = Pegawai::where('email', $request->email)
            ->where('pin', md5($request->pin))
            ->first();

        if (! $pegawai || ! in_array($pegawai->role, ['admin', 'owner', 'kurir'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email atau PIN salah, atau role tidak diizinkan.',
            ], 401);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Login staff berhasil',
            'data' => [
                'pegawai' => $pegawai,
                'role' => $pegawai->role,
                'nama' => $pegawai->nama,
                'token' => Str::random(60),
            ],
        ]);
    }

    public function patientLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'pin' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $pasien = Pasien::where('email', $request->email)
            ->where('pin', md5($request->pin))
            ->first();

        if (! $pasien) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email atau PIN pasien salah.',
            ], 401);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Login pasien berhasil',
            'data' => [
                'pasien' => $pasien,
                'token' => Str::random(60),
            ],
        ]);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama'     => 'required|string|max:100',
            'email'    => 'required|email|max:100|unique:pasien,email',
            'no_telp'  => 'required|string|max:20',
            'pin'      => 'required|string|min:4',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $pasien = Pasien::create([
            'nama'         => $request->nama,
            'email'        => $request->email,
            'pin'          => md5($request->pin),
            'no_telp'      => $request->no_telp,
            'nama_wali'    => null,
            'no_telp_wali' => null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pendaftaran berhasil',
            'data' => [
                'pasien' => $pasien,
                'token' => Str::random(60),
            ],
        ], 201);
    }

    public function getProfile($id)
    {
        $pasien = Pasien::find($id);

        if (! $pasien) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pasien tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $pasien,
        ]);
    }

    public function updateProfile(Request $request, $id)
    {
        $pasien = Pasien::find($id);

        if (! $pasien) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pasien tidak ditemukan.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama'    => 'required|string|max:100',
            'no_telp' => 'required|string|max:20',
            'alamat'  => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $pasien->update($validator->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Profil berhasil diperbarui',
            'data' => $pasien,
        ]);
    }

    public function changePin(Request $request, $id)
    {
        $pasien = Pasien::find($id);

        if (! $pasien) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pasien tidak ditemukan.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'pin_lama'         => 'required|string',
            'pin_baru'         => 'required|string|min:4',
            'konfirmasi_pin'   => 'required|string|same:pin_baru',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($pasien->pin !== md5($request->pin_lama)) {
            return response()->json([
                'status' => 'error',
                'message' => 'PIN lama tidak sesuai.',
            ], 401);
        }

        $pasien->update(['pin' => md5($request->pin_baru)]);

        return response()->json([
            'status' => 'success',
            'message' => 'PIN berhasil diubah',
        ]);
    }
}
