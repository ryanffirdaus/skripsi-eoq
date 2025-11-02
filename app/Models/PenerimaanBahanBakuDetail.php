<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenerimaanBahanBakuDetail extends Model
{
    use HasFactory;

    protected $primaryKey = 'penerimaan_detail_id';
    protected $keyType = 'string';
    protected $table = 'penerimaan_bahan_baku_detail';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'penerimaan_detail_id',
        'penerimaan_id',
        'pembelian_detail_id',
        'bahan_baku_id',
        'qty_diterima',
    ];

    protected $casts = [
        'qty_diterima' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (!$model->getKey()) {
                $latest = static::withTrashed()->orderBy('penerimaan_detail_id', 'desc')->first();
                $nextNumber = $latest ? (int)substr($latest->penerimaan_detail_id, 4) + 1 : 1;
                $model->{$model->getKeyName()} = 'RBMD' . str_pad($nextNumber, 7, '0', STR_PAD_LEFT);
            }
        });
    }

    // Relasi ke header penerimaan
    public function penerimaanBahanBaku()
    {
        return $this->belongsTo(PenerimaanBahanBaku::class, 'penerimaan_id', 'penerimaan_id');
    }

    // Relasi ke detail pembelian asal
    public function pembelianDetail()
    {
        return $this->belongsTo(PembelianDetail::class, 'pembelian_detail_id', 'pembelian_detail_id');
    }

    // Relasi langsung ke BahanBaku
    public function bahanBaku()
    {
        return $this->belongsTo(BahanBaku::class, 'bahan_baku_id', 'bahan_baku_id');
    }
}
