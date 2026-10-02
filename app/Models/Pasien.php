<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pasien extends Model
{
    use HasFactory;

    protected $table = 'pasien';
    protected $primaryKey = 'id_pasien';
    public $timestamps = false;

    protected $fillable = [
        'nama',
        'email',
        'pin',
        'no_telp',
        'nama_wali',
        'no_telp_wali',
        'alamat',
    ];

        protected $hidden = [
        'pin',
    ];
}
