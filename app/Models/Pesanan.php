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
        'tanggal_pesanan',
        'total_harga',
        'status',
        'catatan',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Generate the next ID if not provided
            if (!$model->pesanan_id) {
                $latest = static::orderBy('pesanan_id', 'desc')->first();
                $nextId = $latest ? (int) substr($latest->pesanan_id, 2) + 1 : 1;
                $model->pesanan_id = 'PS' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
            }

            if (Auth::id()) {
                $model->created_by = Auth::id();
                $model->updated_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::id()) {
                $model->updated_by = Auth::id();
            }
        });

        static::deleting(function ($model) {
            if (Auth::id()) {
                $model->deleted_by = Auth::id();
            }
        });
    }

    public function produk()
    {
        return $this->belongsToMany(Produk::class, 'pesanan_produk', 'pesanan_id', 'produk_id')
            ->withPivot('jumlah_produk', 'harga_satuan', 'subtotal')
            ->withTimestamps();
    }

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id', 'pelanggan_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'user_id');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by', 'user_id');
    }

    public function pengiriman()
    {
        return $this->hasMany(Pengiriman::class, 'pesanan_id', 'pesanan_id');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'pesanan_id';
    }
}
