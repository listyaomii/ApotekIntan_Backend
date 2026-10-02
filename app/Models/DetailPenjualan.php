<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPenjualan extends Model
{
    use HasFactory;

    protected $table = 'detail_penjualan';
    protected $primaryKey = 'id_detail_jual';
    public $timestamps = false;

    protected $fillable = [
        'jumlah',
        'harga_satuan',
        'harga_beli',
        'subtotal',
        'id_penjualan',
        'id_obat',
    ];

    public function obat()
    {
        return $this->belongsTo(Obat::class, 'id_obat', 'id_obat');
    }
}
