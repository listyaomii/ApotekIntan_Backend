<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use App\Models\PesanAntar;
use Illuminate\Http\Request;

class RiwayatController extends Controller
{
    public function index(Request $request)
    {
        $idPasien = $request->query('id_pasien');

        if (! $idPasien) {
            return response()->json([
                'status' => 'error',
                'message' => 'id_pasien wajib diisi.',
            ], 422);
        }

        // Transaksi offline (kasir)
        $offline = Penjualan::with(['detailPenjualan.obat:id_obat,nama_obat'])
            ->where('id_pasien', $idPasien)
            ->get()
            ->map(function ($item) {
                return [
                    'id'           => 'PJ-' . $item->id_penjualan,
                    'tipe'         => 'offline',
                    'tanggal'      => $item->tgl_jual,
                    'status'       => 'selesai', // transaksi kasir selalu lunas/selesai saat itu juga
                    'status_pembayaran' => 'sudah_bayar',
                    'total'        => $item->total,
                    'alamat_pengiriman' => null,
                    'items'        => $item->detailPenjualan->map(function ($d) {
                        return [
                            'nama_obat' => $d->obat->nama_obat ?? 'Obat',
                            'jumlah'    => $d->jumlah,
                            'harga_satuan' => $d->harga_satuan,
                        ];
                    }),
                ];
            });

        // Transaksi online (pesan antar)
        $online = PesanAntar::with(['detailPesanan.obat:id_obat,nama_obat'])
            ->where('id_pasien', $idPasien)
            ->get()
            ->map(function ($item) {
                return [
                    'id'           => 'PA-' . $item->id_pesanan,
                    'tipe'         => 'online',
                    'tanggal'      => $item->tgl_pesan,
                    'status'       => $item->status,
                    'status_pembayaran' => $item->status_pembayaran,
                    'total'        => $item->total,
                    'alamat_pengiriman' => $item->alamat_pengiriman,
                    'items'        => $item->detailPesanan->map(function ($d) {
                        return [
                            'nama_obat' => $d->obat->nama_obat ?? 'Obat',
                            'jumlah'    => $d->jumlah,
                            'harga_satuan' => $d->harga_satuan,
                        ];
                    }),
                ];
            });

        // Gabung dan urutkan terbaru dulu
        $combined = $offline->concat($online)
            ->sortByDesc('tanggal')
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $combined,
        ]);
    }
}