<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Pelanggan extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'pelanggan_id';

    protected $keyType = 'string';

    protected $table = 'pelanggan';

    public $incrementing = false;

    protected $fillable = [
        'pelanggan_id',
        'nama_pelanggan',
        'email_pelanggan',
        'nomor_telepon',
        'alamat_pembayaran',
        'alamat_pengiriman',
        'dibuat_oleh',
        'diubah_oleh',
        'dihapus_oleh'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Generate the next ID if not provided
            if (!$model->pelanggan_id) {
                $latest = static::withTrashed()->orderBy('pelanggan_id', 'desc')->first();
                $nextId = $latest ? (int) substr($latest->pelanggan_id, 2) + 1 : 1;
                $model->pelanggan_id = 'PL' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
            }

            if (Auth::id()) {
                $model->dibuat_oleh = Auth::id();
                $model->diubah_oleh = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::id()) {
                $model->diubah_oleh = Auth::id();
            }
        });

        static::deleting(function ($model) {
            if (Auth::id()) {
                $model->dihapus_oleh = Auth::id();
            }
        });
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

    public function pesanan()
    {
        return $this->hasMany(Pesanan::class, 'pelanggan_id', 'pelanggan_id');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'pelanggan_id';
    }
}
