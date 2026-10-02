<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPembelian extends Model
{
    use HasFactory;

    protected $table = 'detail_pembelian';
    protected $primaryKey = 'id_detail_beli';
    public $timestamps = false;

    protected $fillable = [
        'jumlah',
        'harga_satuan',
        'subtotal',
        'id_pembelian',
        'id_obat',
    ];

    public function obat()
    {
        return $this->belongsTo(Obat::class, 'id_obat', 'id_obat');
    }
}
