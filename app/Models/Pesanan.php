<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Pesanan extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'pesanan_id';

    protected $keyType = 'string';

    protected $table = 'pesanan';

    public $incrementing = false;

    protected $fillable = [
        'pesanan_id',
        'pelanggan_id',
        'tanggal_pemesanan',
        'total_harga',
        'status',
        'catatan',
        'dibuat_oleh',
        'diupdate_oleh',
        'dihapus_oleh'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->pesanan_id) {
                $latest = static::withTrashed()->orderBy('pesanan_id', 'desc')->first();
                $nextId = $latest ? (int) substr($latest->pesanan_id, 2) + 1 : 1;
                $model->pesanan_id = 'PS' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
            }

            if (!$model->status) {
                $model->status = 'pending';
            }

            if (Auth::id()) {
                $model->dibuat_oleh = Auth::id();
                $model->diupdate_oleh = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::id()) {
                $model->diupdate_oleh = Auth::id();
            }
        });

        static::deleting(function ($model) {
            if (Auth::id()) {
                $model->dihapus_oleh = Auth::id();
            }

            // Soft delete all detail items
            $model->detail()->each(function ($detail) {
                $detail->delete();
            });
        });
    }

    public function detail()
    {
        return $this->hasMany(PesananDetail::class, 'pesanan_id', 'pesanan_id');
    }

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id', 'pelanggan_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh', 'user_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'diupdate_oleh', 'user_id');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'dihapus_oleh', 'user_id');
    }

    public function pengadaan()
    {
        return $this->hasMany(Pengadaan::class, 'pesanan_id', 'pesanan_id');
    }

    public function pengiriman()
    {
        return $this->hasMany(Pengiriman::class, 'pesanan_id', 'pesanan_id');
    }

    /**
     * Update total harga from detail
     */
    public function updateTotalHarga()
    {
        $this->total_harga = $this->detail()->sum('subtotal');
        $this->saveQuietly();
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'pesanan_id';
    }
}
