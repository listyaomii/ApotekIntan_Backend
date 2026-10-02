<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notifikasi extends Model
{
    use HasFactory;

    protected $table = 'notifikasi';
    protected $primaryKey = 'id_notifikasi';
    public $timestamps = false;

    protected $fillable = [
        'judul',
        'pesan',
        'status',
        'tgl_kirim',
        'tgl_konfirmasi',
        'id_reminder',
        'id_pasien',
        'id_pesanan',
    ];
}
