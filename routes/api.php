<?php

use App\Http\Controllers\DetailPembelianController;
use App\Http\Controllers\DetailPenjualanController;
use App\Http\Controllers\DetailPesananController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\ObatController;
use App\Http\Controllers\PasienController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PesanAntarController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\SatuanController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\MutasiStokController;
use App\Http\Controllers\RiwayatController;
use Illuminate\Support\Facades\Route;

Route::post('auth/staff-login', [AuthController::class, 'staffLogin']);
Route::post('auth/patient-login', [AuthController::class, 'patientLogin']);
Route::post('patient-register', [AuthController::class, 'register']);
Route::get('patient/{id}/profile', [AuthController::class, 'getProfile']);
Route::put('patient/{id}/profile', [AuthController::class, 'updateProfile']);
Route::put('patient/{id}/change-pin', [AuthController::class, 'changePin']);
Route::get('riwayat', [RiwayatController::class, 'index']);
Route::patch('reminder/{id}/toggle-status', [ReminderController::class, 'toggleStatus']);
Route::post('obat/{id}/upload-gambar', [ObatController::class, 'uploadGambar']);

// Obat custom endpoints
Route::get('obat/out-of-stock', [ObatController::class, 'outOfStock']);
Route::get('obat/nearly-expired', [ObatController::class, 'nearlyExpired']);
Route::get('obat/expired', [ObatController::class, 'expired']);

//pegawai custom endpoints
Route::get('/pegawai/admin', [PegawaiController::class, 'admin']);
Route::get('/pegawai/kurir', [PegawaiController::class, 'kurir']);
Route::post('/pegawai/admin', [PegawaiController::class, 'storeAdmin']);
Route::post('/pegawai/kurir', [PegawaiController::class, 'storeKurir']);


Route::get('/obat/count', [ObatController::class, 'count']);
Route::get('/obat/stok-habis', [ObatController::class, 'stokHabis']);
Route::get('/detail-penjualan/terlaris', [DetailPenjualanController::class, 'terlaris']);
Route::get('/penjualan/count', [PenjualanController::class, 'count']);
Route::get('/penjualan/pendapatan', [PenjualanController::class, 'pendapatan']);
Route::get('/penjualan/laporan', [PenjualanController::class, 'indexLaporan']);
Route::get('/penjualan/grafik-mingguan', [PenjualanController::class, 'grafikMingguan']);
Route::get('/pembelian/laporan', [PembelianController::class, 'indexLaporan']);
Route::get('/obat/laporan-stok', [ObatController::class, 'laporanStok']);
Route::get('/penjualan/grafik', [PenjualanController::class, 'grafikPenjualan']);
Route::get('/penjualan/grafik-laba', [PenjualanController::class, 'grafikLaba']);
Route::get('/detail-penjualan/terlaris-periode', [DetailPenjualanController::class, 'terlarisByPeriode']);
Route::get('/penjualan/pendapatan-bulan-ini', [PenjualanController::class, 'pendapatanBulanIni']);
Route::get('/penjualan/count-bulan-ini', [PenjualanController::class, 'countBulanIni']);
Route::get('obat/status-stok', [ObatController::class, 'statusStok']);

Route::get('/mutasi-stok/masuk', [MutasiStokController::class, 'masuk']);
Route::get('/mutasi-stok/keluar', [MutasiStokController::class, 'keluar']);
Route::get('/mutasi-stok', [MutasiStokController::class, 'index']);

Route::apiResource('obat', ObatController::class);
Route::apiResource('kategori', KategoriController::class);
Route::apiResource('satuan', SatuanController::class);
Route::apiResource('supplier', SupplierController::class);
Route::apiResource('pasien', PasienController::class);
Route::apiResource('pegawai', PegawaiController::class);
Route::apiResource('pembelian', PembelianController::class);
Route::apiResource('penjualan', PenjualanController::class);
// Route::apiResource('pesan-antar', PesanAntarController::class);
Route::apiResource('reminder', ReminderController::class);
Route::apiResource('notifikasi', NotifikasiController::class);
Route::apiResource('detail-pembelian', DetailPembelianController::class);
Route::apiResource('detail-penjualan', DetailPenjualanController::class);
// Route::apiResource('detail-pesanan', DetailPesananController::class);
Route::get('/pesan-antar', [PesanAntarController::class, 'index']);
Route::post('/pesan-antar', [PesanAntarController::class, 'store']);
Route::get('/pesan-antar/{id}', [PesanAntarController::class, 'show']);
Route::patch('/pesan-antar/{id}/status', [PesanAntarController::class, 'updateStatus']);
Route::patch('/pesan-antar/{id}/pembayaran', [PesanAntarController::class, 'updatePembayaran']);
Route::delete('/pesan-antar/{id}', [PesanAntarController::class, 'destroy']);


