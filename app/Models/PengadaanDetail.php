<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengadaanDetail extends Model
{
    use HasFactory;

    protected $primaryKey = 'pengadaan_detail_id';
    protected $keyType = 'string';
    protected $table = 'pengadaan_detail';
    public $incrementing = false;

    protected $fillable = [
        'pengadaan_detail_id',
        'pengadaan_id',
        'item_type',
        'item_id',
        'nama_item',
        'satuan',
        'qty_diminta',
        'qty_disetujui',
        'qty_diterima',
        'harga_satuan',
        'total_harga',
        'catatan',
        'alasan_kebutuhan'
    ];

    protected $casts = [
        'qty_diminta' => 'integer',
        'qty_disetujui' => 'integer',
        'qty_diterima' => 'integer',
        'harga_satuan' => 'decimal:2',
        'total_harga' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->pengadaan_detail_id) {
                $latest = static::orderBy('pengadaan_detail_id', 'desc')->first();
                $nextNumber = $latest ? (int)substr($latest->pengadaan_detail_id, 3) + 1 : 1;
                $model->pengadaan_detail_id = 'PGD' . str_pad($nextNumber, 7, '0', STR_PAD_LEFT);
            }
        });

        static::saved(function ($model) {
            // Update total_harga when qty or harga_satuan changes
            $model->where('pengadaan_detail_id', $model->pengadaan_detail_id)
                ->update(['total_harga' => ($model->qty_disetujui ?? $model->qty_diminta) * $model->harga_satuan]);

            // Update pengadaan total
            $model->pengadaan->updateTotalBiaya();
        });
    }

    // Relationships
    public function pengadaan()
    {
        return $this->belongsTo(Pengadaan::class, 'pengadaan_id', 'pengadaan_id');
    }

    public function bahanBaku()
    {
        return $this->belongsTo(BahanBaku::class, 'item_id', 'bahan_baku_id');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'item_id', 'produk_id');
    }

    // Accessor for item relationship
    public function getItemAttribute()
    {
        if ($this->item_type === 'bahan_baku') {
            return $this->bahanBaku;
        } elseif ($this->item_type === 'produk') {
            return $this->produk;
        }
        return null;
    }

    // Business logic methods
    public function isFullyReceived()
    {
        return $this->qty_diterima >= ($this->qty_disetujui ?? $this->qty_diminta);
    }

    public function getOutstandingQty()
    {
        return ($this->qty_disetujui ?? $this->qty_diminta) - $this->qty_diterima;
    }

    // Scope methods
    public function scopeByItemType($query, $type)
    {
        return $query->where('item_type', $type);
    }

    public function scopeByItem($query, $type, $id)
    {
        return $query->where('item_type', $type)->where('item_id', $id);
    }

    public function scopeOutstanding($query)
    {
        return $query->whereRaw('qty_diterima < COALESCE(qty_disetujui, qty_diminta)');
    }
}
