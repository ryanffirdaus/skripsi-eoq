<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class TransaksiPembayaran extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'transaksi_pembayaran_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'transaksi_pembayaran';

    protected $fillable = [
        'transaksi_pembayaran_id',
        'pembelian_id',
        'jenis_pembayaran', // 'dp', 'termin', 'pelunasan'
        'tanggal_pembayaran',
        'total_pembayaran',
        'bukti_pembayaran',
        'catatan',
        'dibuat_oleh',
        'diubah_oleh',
        'dihapus_oleh',
    ];

    protected $casts = [
        'tanggal_pembayaran' => 'date',
        'total_pembayaran' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->transaksi_pembayaran_id) {
                $latest = static::withTrashed()->orderBy('transaksi_pembayaran_id', 'desc')->first();
                $nextNumber = $latest ? (int)substr($latest->transaksi_pembayaran_id, 2) + 1 : 1;
                $model->transaksi_pembayaran_id = 'TP' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }

            if (Auth::check()) {
                $model->dibuat_oleh = Auth::user()->user_id;
                $model->diubah_oleh = Auth::user()->user_id;
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->diubah_oleh = Auth::user()->user_id;
            }
        });

        static::deleting(function ($model) {
            if (Auth::check()) {
                $model->dihapus_oleh = Auth::user()->user_id;
            }
        });
    }

    public function pembelian()
    {
        return $this->belongsTo(Pembelian::class, 'pembelian_id', 'pembelian_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh', 'user_id');
    }

    public function diubahOleh()
    {
        return $this->belongsTo(User::class, 'diubah_oleh', 'user_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'diubah_oleh', 'user_id');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'dihapus_oleh', 'user_id');
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
