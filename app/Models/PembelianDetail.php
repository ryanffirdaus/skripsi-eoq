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
    ];

    protected $casts = [
        // Quantities and prices are accessed via pengadaanDetail relationship
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->pembelian_detail_id) {
                $latest = static::withTrashed()->orderBy('pembelian_detail_id', 'desc')->first();
                $nextNumber = $latest ? (int)substr($latest->pembelian_detail_id, 4) + 1 : 1;
                $model->pembelian_detail_id = 'PBLD' . str_pad($nextNumber, 7, '0', STR_PAD_LEFT);
            }
        });

        static::saved(function ($model) {
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

    public function penerimaanBahanBaku()
    {
        return $this->hasMany(PenerimaanBahanBaku::class, 'pembelian_detail_id', 'pembelian_detail_id');
    }

    // Business Logic Methods
    public function getQtyDipesanAttribute()
    {
        return $this->pengadaanDetail->qty_disetujui ?? $this->pengadaanDetail->qty_diminta;
    }

    public function getHargaSatuanAttribute()
    {
        return $this->pengadaanDetail->harga_satuan;
    }

    public function getTotalHargaAttribute()
    {
        return $this->qty_dipesan * $this->harga_satuan;
    }

    public function isFullyReceived()
    {
        $totalDiterima = $this->penerimaanBahanBaku()->sum('qty_diterima');
        $qtyDipesan = $this->qty_dipesan;
        return $totalDiterima >= $qtyDipesan;
    }

    public function getOutstandingQty()
    {
        $totalDiterima = $this->penerimaanBahanBaku()->sum('qty_diterima');
        return $this->qty_dipesan - $totalDiterima;
    }
}
