<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReminderController extends Controller
{
    // Relasi obat yang di-eager-load setiap kali reminder dikirim ke Flutter,
    // supaya field 'gambar_url' (accessor di model Obat) ikut ke response.
    // id_kategori ikut di-select karena dibutuhkan relasi kategori() di dalam
    // model Obat ($with = ['kategori']) — kalau tidak, kategori akan null.
    private const OBAT_RELATION = 'obat:id_obat,nama_obat,gambar,id_kategori';

    public function index(Request $request)
    {
        $idPasien = $request->query('id_pasien');

        $query = Reminder::with(self::OBAT_RELATION);

        if ($idPasien) {
            $query->where('id_pasien', $idPasien);
        }

        $data = $query->orderBy('id_reminder', 'desc')->get();

        return response()->json($data);
    }

    public function store(Request $request)
    {
        if ($request->tgl_selesai === '') {
        $request->merge(['tgl_selesai' => null]);
        }
        $validator = Validator::make($request->all(), [
            'id_pasien'         => 'required|exists:pasien,id_pasien',
            'id_obat'           => 'nullable|exists:obat,id_obat',
            'id_pegawai'        => 'nullable|exists:pegawai,id_pegawai',
            'nama_obat'         => 'required|string|max:150',
            'dosis'             => 'required|string|max:100',
            'jam_minum_list'    => 'required|array|min:1',
            'jam_minum_list.*' => ['required', 'string', 'regex:/^\d{1,2}:\d{2}$/'],
            'frekuensi'         => 'required|string|max:50',
            'hari_aktif'        => 'required|array|size:7',
            'hari_aktif.*'      => 'boolean',
            'stok'              => 'nullable|integer|min:0',
            'tgl_mulai'         => 'required|date',
            'tgl_selesai'       => 'nullable|date|after_or_equal:tgl_mulai',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['status_aktif'] = true;
        $data['id_obat'] = $data['id_obat'] ?? null;
        $data['id_pegawai'] = $data['id_pegawai'] ?? null;

        // Handle tgl_selesai kosong dari Flutter
        if (empty($data['tgl_selesai'])) {
            $data['tgl_selesai'] = null;
        }

        $item = Reminder::create($data);
        $item->load(self::OBAT_RELATION);

        return response()->json($item, 201);
    }

    public function show($id)
    {
        $item = Reminder::with(self::OBAT_RELATION)->findOrFail($id);
        return response()->json($item);
    }

    public function update(Request $request, $id)
    {
        $item = Reminder::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'id_obat'           => 'nullable|exists:obat,id_obat',
            'nama_obat'         => 'sometimes|string|max:150',
            'dosis'             => 'sometimes|string|max:100',
            'jam_minum_list'    => 'sometimes|array|min:1',
            'jam_minum_list.*' => ['required', 'string', 'regex:/^\d{1,2}:\d{2}$/'],
            'frekuensi'         => 'sometimes|string|max:50',
            'hari_aktif'        => 'sometimes|array|size:7',
            'hari_aktif.*'      => 'boolean',
            'status_aktif'      => 'sometimes|boolean',
            'stok'              => 'nullable|integer|min:0',
            'tgl_mulai'         => 'sometimes|date',
            'tgl_selesai'       => 'sometimes|nullable|date|after_or_equal:tgl_mulai',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $item->update($validator->validated());
        $item->load(self::OBAT_RELATION);

        return response()->json($item);
    }

    public function toggleStatus(Request $request, $id)
    {
        $item = Reminder::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status_aktif' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        $item->update(['status_aktif' => $request->status_aktif]);

        return response()->json($item);
    }

    public function destroy($id)
    {
        Reminder::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}