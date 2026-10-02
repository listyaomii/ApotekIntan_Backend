<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPesanan extends Model
{
    use HasFactory;

    protected $table = 'detail_pesanan';
    protected $primaryKey = 'id_detail_pesanan';
    public $timestamps = false;

    protected $fillable = [
        'jumlah',
        'harga_satuan',
        'subtotal',
        'id_pesanan',
        'id_obat',
    ];

    public function obat()
    {
        return $this->belongsTo(Obat::class, 'id_obat', 'id_obat');
    }
}
