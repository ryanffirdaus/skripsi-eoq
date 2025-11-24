<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Produk extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'produk_id';

    protected $keyType = 'string';

    protected $table = 'produk';

    public $incrementing = false;

    protected $fillable = [
        'produk_id',
        'nama_produk',
        'stok_produk',
        'satuan_produk',
        'lokasi_produk',
        'hpp_produk',
        'harga_jual',
        'permintaan_harian_rata2_produk',
        'permintaan_harian_maksimum_produk',
        'waktu_tunggu_rata2_produk',
        'waktu_tunggu_maksimum_produk',
        'permintaan_tahunan',
        'biaya_pemesanan_produk',
        'biaya_penyimpanan_produk',
        'safety_stock_produk',
        'rop_produk',
        'eoq_produk',
        'dibuat_oleh',
        'diubah_oleh',
        'dihapus_oleh'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Generate the next ID if not provided
            if (!$model->produk_id) {
                $latest = static::withTrashed()->orderByRaw('CAST(SUBSTRING(produk_id, 3) AS UNSIGNED) DESC')->first();
                $nextId = $latest ? (int) substr($latest->produk_id, 2) + 1 : 1;
                $model->produk_id = 'PP' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
            }
        });

        static::created(function ($model) {
            $model->dibuat_oleh = Auth::id();
            $model->diubah_oleh = Auth::id();
            $model->saveQuietly(); // Prevent triggering events again
        });

        static::updating(function ($model) {
            $model->diubah_oleh = Auth::id();
            $model->saveQuietly(); // Prevent triggering events again
        });

        static::deleting(function ($model) {
            $model->dihapus_oleh = Auth::id();
            $model->saveQuietly(); // Prevent triggering events again
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

    public function bahanBaku()
    {
        return $this->belongsToMany(BahanBaku::class, 'bahan_produksi', 'produk_id', 'bahan_baku_id')
            ->withPivot('jumlah_bahan_baku');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'produk_id';
    }
}
