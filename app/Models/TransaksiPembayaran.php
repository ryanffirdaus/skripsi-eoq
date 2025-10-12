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
        'jenis_pembayaran', // 'dp', 'termin', 'pelunasan'
        'tanggal_pembayaran',
        'total_pembayaran',
        'bukti_pembayaran',
        'deskripsi',
    ];

    protected $casts = [
        'tanggal_pembayaran' => 'date',
        'total_pembayaran' => 'decimal:2',
    ];

    public function pembelian()
    {
        return $this->belongsTo(Pembelian::class, 'pembelian_id', 'pembelian_id');
    }

    /**
     * Get total pembayaran yang sudah dilakukan untuk pembelian ini
     */
    public function getTotalPembayaranAttribute()
    {
        return static::where('pembelian_id', $this->pembelian_id)
            ->sum('total_pembayaran');
    }

    /**
     * Get sisa pembayaran yang belum dibayar
     */
    public function getSisaPembayaranAttribute()
    {
        $totalBiaya = $this->pembelian->total_biaya;
        $totalDibayar = static::where('pembelian_id', $this->pembelian_id)
            ->sum('total_pembayaran');
        return $totalBiaya - $totalDibayar;
    }
}
