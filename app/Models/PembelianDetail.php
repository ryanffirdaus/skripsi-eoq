<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembelianDetail extends Model
{
    use HasFactory;

    protected $table = 'pembelian_detail';
    protected $primaryKey = 'pembelian_detail_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'pembelian_detail_id',
        'pembelian_id',
        'pengadaan_detail_id',
        'item_type',
        'item_id',
        'nama_item',
        'satuan',
        'qty_po',
        'qty_diterima',
        'harga_satuan',
        'total_harga',
        'spesifikasi',
        'catatan',
    ];

    protected $casts = [
        'qty_po' => 'integer',
        'qty_diterima' => 'integer',
        'harga_satuan' => 'decimal:2',
        'total_harga' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->pembelian_detail_id)) {
                $lastId = static::where('pembelian_id', $model->pembelian_id)->count();
                $model->pembelian_detail_id = $model->pembelian_id . '-' . str_pad($lastId + 1, 3, '0', STR_PAD_LEFT);
            }
        });

        static::saving(function ($model) {
            // Auto calculate total_harga
            $model->total_harga = $model->qty_po * $model->harga_satuan;
        });
    }

    // Relationships
    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class, 'pembelian_id', 'pembelian_id');
    }

    public function pengadaanDetail(): BelongsTo
    {
        return $this->belongsTo(PengadaanDetail::class, 'pengadaan_detail_id', 'pengadaan_detail_id');
    }

    public function bahanBaku(): BelongsTo
    {
        return $this->belongsTo(BahanBaku::class, 'item_id', 'bahan_baku_id')
            ->where('item_type', 'bahan_baku');
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'item_id', 'produk_id')
            ->where('item_type', 'produk');
    }

    // Accessors
    public function getOutstandingQtyAttribute(): int
    {
        return $this->qty_po - $this->qty_diterima;
    }

    public function getReceivedPercentageAttribute(): int
    {
        return $this->qty_po > 0 ? round(($this->qty_diterima / $this->qty_po) * 100) : 0;
    }

    public function getIsFullyReceivedAttribute(): bool
    {
        return $this->qty_diterima >= $this->qty_po;
    }

    public function getFormattedTotalAttribute(): string
    {
        return 'Rp ' . number_format($this->total_harga, 0, ',', '.');
    }

    // Business Logic Methods
    public function canReceiveQuantity(int $quantity): bool
    {
        return ($this->qty_diterima + $quantity) <= $this->qty_po;
    }

    public function receiveQuantity(int $quantity): bool
    {
        if (!$this->canReceiveQuantity($quantity)) {
            return false;
        }

        $this->qty_diterima += $quantity;
        return $this->save();
    }

    public function getItem()
    {
        return $this->item_type === 'bahan_baku'
            ? $this->bahanBaku
            : $this->produk;
    }
}
