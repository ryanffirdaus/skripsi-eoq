<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PesananDetail extends Model
{
    use HasFactory;

    protected $primaryKey = 'pesanan_detail_id';
    protected $keyType = 'string';
    protected $table = 'pesanan_detail';
    public $incrementing = false;

    protected $fillable = [
        'pesanan_detail_id',
        'pesanan_id',
        'produk_id',
        'jumlah_produk',
        'harga_satuan',
        'subtotal',
    ];

    protected $casts = [
        'jumlah_produk' => 'integer',
        'harga_satuan' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->pesanan_detail_id) {
                $latest = static::withTrashed()->orderBy('pesanan_detail_id', 'desc')->first();
                $nextNumber = $latest ? (int)substr($latest->pesanan_detail_id, 4) + 1 : 1;
                $model->pesanan_detail_id = 'PSND' . str_pad($nextNumber, 7, '0', STR_PAD_LEFT);
            }

            // Auto-calculate subtotal
            if (!$model->subtotal) {
                $model->subtotal = $model->jumlah_produk * $model->harga_satuan;
            }
        });

        static::saved(function ($model) {
            // Update subtotal if quantity or price changes
            if ($model->isDirty('jumlah_produk') || $model->isDirty('harga_satuan')) {
                $model->subtotal = $model->jumlah_produk * $model->harga_satuan;
                $model->saveQuietly();
            }

            // Update total_harga di header pesanan
            $model->pesanan->updateTotalHarga();
        });

        static::deleted(function ($model) {
            // Update total harga di header setelah item dihapus
            if ($model->pesanan) {
                $model->pesanan->updateTotalHarga();
            }
        });
    }

    // Relationships
    public function pesanan()
    {
        return $this->belongsTo(Pesanan::class, 'pesanan_id', 'pesanan_id');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id', 'produk_id');
    }
}
