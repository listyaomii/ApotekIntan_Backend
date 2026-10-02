<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PesanAntar extends Model
{
    use HasFactory;

    protected $table = 'pesan_antar';
    protected $primaryKey = 'id_pesanan';
    public $timestamps = false;

    protected $fillable = [
        'alamat_pengiriman',
        'status',
        'total',
        'metode_pembayaran',
        'status_pembayaran',
        'keterangan',
        'tgl_pesan',
        'tgl_konfirmasi',
        'id_pasien',
        'id_pegawai_admin',
        'id_pegawai_kurir',
    ];

    public function detailPesanan()
    {
        return $this->hasMany(DetailPesanan::class, 'id_pesanan', 'id_pesanan');
    }
}
