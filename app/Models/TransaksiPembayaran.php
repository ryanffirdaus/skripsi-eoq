<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaksiPembayaran extends Model
{
    use HasFactory;

    protected $primaryKey = 'transaksi_pembayaran_id';
    public $incrementing = false;
    protected $keyType = 'string';


    protected $fillable = [
        'transaksi_pembayaran_id',
        'pembelian_id',
        'tanggal_pembayaran',
        'total_pembayaran',
        'bukti_pembayaran',
        'deskripsi',
    ];

    public function pembelian()
    {
        return $this->belongsTo(Pembelian::class, 'pembelian_id', 'pembelian_id');
    }
}
