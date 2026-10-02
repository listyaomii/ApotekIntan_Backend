<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Obat extends Model
{
    use HasFactory;

    protected $table = 'obat';
    protected $primaryKey = 'id_obat';
    public $timestamps = false;

    protected $fillable = [
        'nama_obat',
        'gambar',
        'harga_beli',
        'harga_jual',
        'stok',
        'stok_minimum',
        'tgl_kadaluarsa',
        'deskripsi',
        'barcode',
        'id_kategori',
        'id_satuan',
        'id_supplier',
    ];

    protected $with = ['kategori'];
    protected $appends = ['nama_kategori', 'gambar_url'];
    protected $hidden = ['id_kategori', 'kategori'];
    

    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'id_kategori', 'id_kategori');
    }

    public function getNamaKategoriAttribute()
    {
        return $this->kategori?
            $this->kategori->nama_kategori : null;
    }

    public function getGambarUrlAttribute(): ?string
    {
        if (!$this->gambar) return null;
        return url('storage/' . $this->gambar);
        // Hasil: http://10.113.152.61:8000/storage/obat/paracetamol.jpg
    }
}
