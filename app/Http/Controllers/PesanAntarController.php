<?php

namespace App\Http\Controllers;

use App\Models\PesanAntar;
use App\Models\DetailPesanan;
use App\Models\Obat;
use App\Models\Reminder;
use Illuminate\Http\Request;

class PesanAntarController extends Controller
{
public function index(Request $request)
{
    $idPasien = $request->query('id_pasien');
    $idPegawaiKurir = $request->query('id_pegawai_kurir');

    $query = PesanAntar::select(
            'pesan_antar.id_pesanan',
            'pesan_antar.alamat_pengiriman',
            'pesan_antar.status',
            'pesan_antar.total',
            'pesan_antar.metode_pembayaran',
            'pesan_antar.status_pembayaran',
            'pesan_antar.keterangan',
            'pesan_antar.tgl_pesan',
            'pesan_antar.id_pegawai_kurir',
            'pasien.nama as nama_pasien'
        )
        ->join('pasien', 'pesan_antar.id_pasien', '=', 'pasien.id_pasien')
        ->with(['detailPesanan.obat:id_obat,nama_obat']);

    if ($idPasien) {
        $query->where('pesan_antar.id_pasien', $idPasien);
    }

    // Filter khusus kurir: hanya tampilkan pesanan yang sudah di-assign
    // ke kurir ini oleh admin (id_pegawai_kurir sudah diisi).
    if ($idPegawaiKurir) {
        $query->where('pesan_antar.id_pegawai_kurir', $idPegawaiKurir);
    }

    $pesanan = $query->orderBy('pesan_antar.tgl_pesan', 'desc')->paginate(10);

    return response()->json($pesanan);
}

    public function store(Request $request)
    {
        $request->validate([
            'alamat_pengiriman'     => 'required|string',
            'metode_pembayaran'     => 'required|in:cash,transfer',
            'keterangan'            => 'nullable|string',
            'id_pasien'             => 'required|exists:pasien,id_pasien',
            'detail'                => 'required|array|min:1',
            'detail.*.id_obat'      => 'required|exists:obat,id_obat',
            'detail.*.jumlah'       => 'required|integer|min:1',
            'detail.*.harga_satuan' => 'required|numeric|min:0',
            'detail.*.subtotal'     => 'required|numeric|min:0',
            // 'total' sengaja TIDAK divalidasi/dipakai dari request lagi,
            // karena dihitung ulang di server supaya tidak bisa dimanipulasi dari client.
        ]);

        // Subtotal belanja = jumlah semua subtotal item (belum termasuk ongkir)
        $subtotalBelanja = collect($request->detail)->sum('subtotal');

        // Aturan ongkir: subtotal < 10.000 kena ongkir 5.000, >= 10.000 gratis ongkir
        $ongkir = $subtotalBelanja < 10000 ? 5000 : 0;

        $totalAkhir = $subtotalBelanja + $ongkir;

        // Cek stok cukup untuk semua item SEBELUM transaksi dimulai
        foreach ($request->detail as $item) {
            $obat = Obat::findOrFail($item['id_obat']);
            if ($obat->stok < $item['jumlah']) {
                return response()->json([
                    'message' => "Stok {$obat->nama_obat} tidak mencukupi. Stok tersedia: {$obat->stok}",
                ], 422);
            }
        }

        // Bungkus dalam transaksi DB supaya kalau ada yang gagal, semua di-rollback
        $pesanan = \DB::transaction(function () use ($request, $totalAkhir) {
            $pesanan = PesanAntar::create([
                'alamat_pengiriman' => $request->alamat_pengiriman,
                'status'            => 'menunggu',
                'total'             => $totalAkhir, // total sudah termasuk ongkir
                'metode_pembayaran' => $request->metode_pembayaran,
                'status_pembayaran' => 'belum_bayar',
                'keterangan'        => $request->keterangan,
                'tgl_pesan'         => now(),
                'id_pasien'         => $request->id_pasien,
            ]);

            foreach ($request->detail as $item) {
                DetailPesanan::create([
                    'id_pesanan'   => $pesanan->id_pesanan,
                    'id_obat'      => $item['id_obat'],
                    'jumlah'       => $item['jumlah'],
                    'harga_satuan' => $item['harga_satuan'],
                    'subtotal'     => $item['subtotal'],
                ]);

                // Kurangi stok
                Obat::where('id_obat', $item['id_obat'])
                    ->decrement('stok', $item['jumlah']);
            }

            return $pesanan;
        });

        return response()->json([
            'message'          => 'Pesanan berhasil dibuat',
            'data'             => $pesanan->load('detailPesanan'),
            'subtotal_belanja' => $subtotalBelanja,
            'ongkir'           => $ongkir,
            'total'            => $totalAkhir,
        ], 201);
    }

    public function show($id)
    {
        $pesanan = PesanAntar::select(
                'pesan_antar.*',
                'pasien.nama as nama_pasien'
            )
            ->join('pasien', 'pesan_antar.id_pasien', '=', 'pasien.id_pasien')
            ->findOrFail($id);

        return response()->json($pesanan);
    }

    // public function updateStatus(Request $request, $id)
    // {
    //     $request->validate([
    //         'status' => 'required|in:menunggu,dikonfirmasi,dikirim,selesai,ditolak,dibatalkan',
    //     ]);

    //     $pesanan = PesanAntar::findOrFail($id);
    //     $pesanan->update([
    //         'status'          => $request->status,
    //         'tgl_konfirmasi'  => in_array($request->status, ['dikonfirmasi', 'ditolak']) ? now() : $pesanan->tgl_konfirmasi,
    //         'id_pegawai_admin' => $request->id_pegawai_admin ?? $pesanan->id_pegawai_admin,
    //         'id_pegawai_kurir' => $request->id_pegawai_kurir ?? $pesanan->id_pegawai_kurir,
    //     ]);

    //     return response()->json([
    //         'message' => 'Status pesanan berhasil diperbarui',
    //         'data'    => $pesanan,
    //     ]);
    // }

    public function updateStatus(Request $request, $id)
{
    $request->validate([
        'status' => 'required|in:menunggu,dikonfirmasi,dikirim,selesai,ditolak,dibatalkan',
    ]);

    $pesanan = PesanAntar::findOrFail($id);
    $statusSebelumnya = $pesanan->status;

    $pesanan->update([
        'status'          => $request->status,
        'tgl_konfirmasi'  => in_array($request->status, ['dikonfirmasi', 'ditolak']) ? now() : $pesanan->tgl_konfirmasi,
        'id_pegawai_admin' => $request->id_pegawai_admin ?? $pesanan->id_pegawai_admin,
        'id_pegawai_kurir' => $request->id_pegawai_kurir ?? $pesanan->id_pegawai_kurir,
    ]);

    // Kalau status baru = "selesai" DAN sebelumnya BUKAN "selesai" (mencegah
    // duplikasi reminder kalau endpoint ini kebetulan dipanggil berkali-kali
    // dengan status "selesai"), buat reminder otomatis (nonaktif) untuk setiap
    // obat dalam pesanan ini. Reminder sengaja dibuat OFF by default supaya
    // pasien sendiri yang memutuskan mau mengaktifkan pengingat atau tidak.
    //
    // Ini hanya diterapkan untuk Pesan Antar (bukan Penjualan kasir), karena
    // id_pasien di pesan_antar selalu terisi (wajib login lewat app Flutter),
    // sedangkan di Penjualan kasir id_pasien sering NULL (opsi "Pasien Umum"),
    // dan Reminder mewajibkan id_pasien terisi.
    if ($request->status === 'selesai' && $statusSebelumnya !== 'selesai') {
        $detailPesanan = DetailPesanan::where('id_pesanan', $pesanan->id_pesanan)->get();

        foreach ($detailPesanan as $item) {
            $obat = Obat::find($item->id_obat);
            if (!$obat) continue; // jaga-jaga kalau obat sudah dihapus dari master data

            $tglMulai = now();

            Reminder::create([
                'id_pasien'      => $pesanan->id_pasien,
                'id_obat'        => $obat->id_obat,
                'id_pegawai'     => null,
                'nama_obat'      => $obat->nama_obat,
                'dosis'          => '1x sesuai kemasan', // default generik, pasien bisa edit sendiri nanti
                'jam_minum_list' => ['08:00'],            // default generik, pasien bisa ubah lewat app
                'frekuensi'      => 'Sekali Sehari',
                'hari_aktif'     => [true, true, true, true, true, true, true], // semua hari aktif
                'stok'           => $item->jumlah, // jumlah yang dibeli, sebagai stok awal
                'tgl_mulai'      => $tglMulai->toDateString(),
                // Kolom tgl_selesai di database bertipe NOT NULL (wajib diisi),
                // jadi tidak bisa dikirim null. Default 1 tahun dari tgl_mulai
                // sebagai "tanpa batas praktis" — pasien tetap bisa menonaktifkan
                // reminder ini kapan saja lewat toggle di app.
                'tgl_selesai'    => $tglMulai->copy()->addYear()->toDateString(),
                'status_aktif'   => false, // OFF by default, pasien yang mengaktifkan sendiri
            ]);
        }
    }

    return response()->json([
        'message' => 'Status pesanan berhasil diperbarui',
        'data'    => $pesanan,
    ]);
}

    public function updatePembayaran(Request $request, $id)
    {
        $request->validate([
            'status_pembayaran' => 'required|in:belum_bayar,sudah_bayar',
        ]);

        $pesanan = PesanAntar::findOrFail($id);
        $pesanan->update([
            'status_pembayaran' => $request->status_pembayaran,
        ]);

        return response()->json([
            'message' => 'Status pembayaran berhasil diperbarui',
            'data'    => $pesanan,
        ]);
    }

    public function destroy($id)
    {
        PesanAntar::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}