<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PembelianDetail extends Model
{
    use HasFactory;

    protected $primaryKey = 'pembelian_detail_id';
    protected $keyType = 'string';
    protected $table = 'pembelian_detail';
    public $incrementing = false;

    protected $fillable = [
        'pembelian_detail_id',
        'pembelian_id',
        'pengadaan_detail_id', // Kunci untuk traceability ke permintaan awal
        'item_type',
        'item_id',
        'nama_item',
        'satuan',
        'qty_dipesan',
        'qty_diterima',
        'harga_satuan',
        'total_harga'
    ];

    protected $casts = [
        'qty_dipesan' => 'integer',
        'qty_diterima' => 'integer',
        'harga_satuan' => 'decimal:2',
        'total_harga' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->pembelian_detail_id) {
                $latest = static::orderBy('pembelian_detail_id', 'desc')->first();
                $nextNumber = $latest ? (int)substr($latest->pembelian_detail_id, 4) + 1 : 1;
                $model->pembelian_detail_id = 'PBLD' . str_pad($nextNumber, 7, '0', STR_PAD_LEFT);
            }
            // Set default qty_diterima
            if (is_null($model->qty_diterima)) {
                $model->qty_diterima = 0;
            }
        });

        static::saved(function ($model) {
            // Hitung ulang total_harga jika ada perubahan pada qty atau harga
            if ($model->isDirty('qty_dipesan') || $model->isDirty('harga_satuan')) {
                $model->total_harga = $model->qty_dipesan * $model->harga_satuan;
                // Simpan tanpa memicu event lagi untuk menghindari loop
                $model->saveQuietly();
            }

            // Update total biaya di header pembelian
            $model->pembelian->updateTotalBiaya();
        });

        static::deleted(function ($model) {
            // Update total biaya di header setelah item dihapus
            if ($model->pembelian) {
                $model->pembelian->updateTotalBiaya();
            }
        });
    }

    // Relationships
    public function pembelian()
    {
        return $this->belongsTo(Pembelian::class, 'pembelian_id', 'pembelian_id');
    }

    public function pengadaanDetail()
    {
        return $this->belongsTo(PengadaanDetail::class, 'pengadaan_detail_id', 'pengadaan_detail_id');
    }

    // Polymorphic relationship untuk item (sama seperti di PengadaanDetail)
    public function bahanBaku()
    {
        return $this->belongsTo(BahanBaku::class, 'item_id', 'bahan_baku_id');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'item_id', 'produk_id');
    }

    public function getItemAttribute()
    {
        if ($this->item_type === 'bahan_baku') {
            return $this->bahanBaku;
        } elseif ($this->item_type === 'produk') {
            return $this->produk;
        }
        return null;
    }

    // Business Logic Methods
    public function isFullyReceived()
    {
        return $this->qty_diterima >= $this->qty_dipesan;
    }

    public function getOutstandingQty()
    {
        return $this->qty_dipesan - $this->qty_diterima;
    }
}
