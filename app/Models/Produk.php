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
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Generate the next ID if not provided
            if (!$model->produk_id) {
                $latest = static::withTrashed()->orderBy('produk_id', 'desc')->first();
                $nextId = $latest ? (int) substr($latest->produk_id, 2) + 1 : 1;
                $model->produk_id = 'PP' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
            }
        });

        static::created(function ($model) {
            $model->created_by = Auth::id();
            $model->updated_by = Auth::id();
            $model->saveQuietly(); // Prevent triggering events again
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id();
            $model->saveQuietly(); // Prevent triggering events again
        });

        static::deleting(function ($model) {
            $model->deleted_by = Auth::id();
            $model->saveQuietly(); // Prevent triggering events again
        });
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

    // Relationship to bahan baku through bahan_produksi table
    public function bahanBakus()
    {
        return $this->belongsToMany(BahanBaku::class, 'bahan_produksi', 'produk_id', 'bahan_baku_id')
                    ->withPivot('jumlah_bahan_baku');
    }
}
