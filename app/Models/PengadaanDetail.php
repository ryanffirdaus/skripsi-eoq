<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PengadaanDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'pengadaan_detail_id';
    protected $keyType = 'string';
    protected $table = 'pengadaan_detail';
    public $incrementing = false;

    protected $fillable = [
        'pengadaan_detail_id',
        'pengadaan_id',
        'pemasok_id',
        'jenis_barang',
        'barang_id',
        'qty_diminta',
        'qty_disetujui',
        'qty_diterima',
        'harga_satuan',
        'biaya_pemesanan',
        'catatan',
    ];

    protected $casts = [
        'qty_diminta' => 'integer',
        'qty_disetujui' => 'integer',
        'qty_diterima' => 'integer',
        'harga_satuan' => 'decimal:2',
    ];

    protected $appends = ['total_harga', 'nama_item', 'satuan', 'total_ditugaskan', 'sisa_quota_produksi'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->pengadaan_detail_id) {
                $latest = static::withTrashed()->orderByRaw('CAST(SUBSTRING(pengadaan_detail_id, 4) AS UNSIGNED) DESC')->first();
                $nextNumber = $latest ? (int)substr($latest->pengadaan_detail_id, 3) + 1 : 1;
                $model->pengadaan_detail_id = 'PGD' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }
        });

        static::saved(function ($model) {
            // total_harga is calculated as an accessor, no need to store in database
            // Note: If Pengadaan model has updateTotalBiaya(), it will be called here
        });
    }

    // Relationships
    public function pengadaan()
    {
        return $this->belongsTo(Pengadaan::class, 'pengadaan_id', 'pengadaan_id');
    }

    public function pemasok()
    {
        return $this->belongsTo(Pemasok::class, 'pemasok_id', 'pemasok_id');
    }

    public function bahanBaku()
    {
        return $this->belongsTo(BahanBaku::class, 'barang_id', 'bahan_baku_id');
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'barang_id', 'produk_id');
    }

    // Accessor for item relationship
    public function getItemAttribute()
    {
        if ($this->jenis_barang === 'bahan_baku') {
            return $this->bahanBaku;
        } elseif ($this->jenis_barang === 'produk') {
            return $this->produk;
        }
        return null;
    }

    public function getTotalHargaAttribute()
    {
        $qty = $this->qty_disetujui ?? $this->qty_diminta;
        return $qty * $this->harga_satuan;
    }

    public function getNamaItemAttribute()
    {
        if ($this->jenis_barang === 'bahan_baku' && $this->bahanBaku) {
            return $this->bahanBaku->nama_bahan;
        } elseif ($this->jenis_barang === 'produk' && $this->produk) {
            return $this->produk->nama_produk;
        }
        return 'N/A';
    }

    public function getSatuanAttribute()
    {
        if ($this->jenis_barang === 'bahan_baku' && $this->bahanBaku) {
            return $this->bahanBaku->satuan_bahan;
        } elseif ($this->jenis_barang === 'produk' && $this->produk) {
            return $this->produk->satuan_produk;
        }

        return '-';
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
        return $query->where('jenis_barang', $type);
    }

    public function scopeByItem($query, $type, $id)
    {
        return $query->where('jenis_barang', $type)->where('barang_id', $id);
    }

    public function scopeOutstanding($query)
    {
        return $query->whereRaw('qty_diterima < COALESCE(qty_disetujui, qty_diminta)');
    }

    // Relationship to PenugasanProduksi
    public function penugasan()
    {
        return $this->hasMany(PenugasanProduksi::class, 'pengadaan_detail_id', 'pengadaan_detail_id');
    }

    /**
     * Get total jumlah produksi yang sudah ditugaskan (tidak termasuk yang dibatalkan)
     */
    public function getTotalDitugaskanAttribute()
    {
        return $this->penugasan()
            ->where('status', '!=', 'dibatalkan')
            ->sum('jumlah_produksi');
    }

    /**
     * Get sisa quota produksi yang bisa ditugaskan
     */
    public function getSisaQuotaProduksiAttribute()
    {
        $maxQty = $this->qty_disetujui ?? $this->qty_diminta;
        $totalDitugaskan = $this->getTotalDitugaskanAttribute();
        return $maxQty - $totalDitugaskan;
    }
}
