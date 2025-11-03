<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Pembelian extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'pembelian_id';
    protected $keyType = 'string';
    protected $table = 'pembelian';
    public $incrementing = false;

    protected $fillable = [
        'pembelian_id',
        'pengadaan_id',
        'pemasok_id',
        'tanggal_pembelian',
        'tanggal_kirim_diharapkan',
        'total_biaya',
        'metode_pembayaran',
        'termin_pembayaran',
        'jumlah_dp',
        'status', // Contoh: draft, sent, confirmed, partially_received, fully_received, cancelled
        'catatan',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'tanggal_pembelian' => 'date',
        'tanggal_kirim_diharapkan' => 'date',
        'total_biaya' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->pembelian_id) {
                // Membuat ID pembelian dengan format PO: PO-YYYYMM-XXXX
                $yearMonth = date('Ym');
                $latestInMonth = static::withTrashed()->where('pembelian_id', 'like', "PO-{$yearMonth}-%")->count();
                $nextPoNumber = $latestInMonth + 1;
                $model->pembelian_id = "PO-" . $yearMonth . "-" . str_pad($nextPoNumber, 4, '0', STR_PAD_LEFT);
            }

            if (Auth::check()) {
                $model->created_by = Auth::user()->user_id;
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::user()->user_id;
            }
        });

        static::deleting(function ($model) {
            if (Auth::check()) {
                $model->deleted_by = Auth::user()->user_id;
                $model->save();
            }

            // Soft delete all detail items
            $model->detail()->each(function ($detail) {
                $detail->delete();
            });
        });

        static::created(function ($model) {
            // Ketika Pembelian dibuat dari Pengadaan, ubah status Pengadaan dari "disetujui_keuangan" menjadi "diproses"
            if ($model->pengadaan_id) {
                $pengadaan = Pengadaan::find($model->pengadaan_id);
                if ($pengadaan && $pengadaan->status === 'disetujui_keuangan') {
                    $pengadaan->update(['status' => 'diproses']);
                }
            }
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

    public function detail()
    {
        return $this->hasMany(PembelianDetail::class, 'pembelian_id', 'pembelian_id');
    }

    public function transaksiPembayaran()
    {
        return $this->hasMany(TransaksiPembayaran::class, 'pembelian_id', 'pembelian_id');
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

    // Business Logic Methods
    public function updateTotalBiaya()
    {
        // Calculate total from pengadaan_detail via pembelian_detail
        $total = 0;
        foreach ($this->detail as $detail) {
            if ($detail->pengadaanDetail) {
                $qty = $detail->pengadaanDetail->qty_disetujui ?? $detail->pengadaanDetail->qty_diminta;
                $total += $qty * $detail->pengadaanDetail->harga_satuan;
            }
        }
        $this->setAttribute('total_biaya', $total);
        $this->saveQuietly();
    }

    public function canBeEdited()
    {
        $user = Auth::user();

        // Admin (R01) dapat edit di SEMUA status tanpa exception
        if ($user && $user->role_id === 'R01') {
            return true;
        }

        // Edit button tampil untuk semua status kecuali cancelled dan fully_received
        return !in_array($this->status, ['cancelled', 'fully_received']);
    }

    public function canBeCancelled()
    {
        // Admin (R01) dapat bypass
        if (Auth::check() && Auth::user()->role_id === 'R01') {
            return true;
        }

        return !in_array($this->status, ['fully_received', 'cancelled']);
    }

    /**
     * Validasi apakah status transition valid
     * Flow: draft → sent → confirmed → partially_received → fully_received
     * Bisa cancelled dari status manapun kecuali fully_received
     */
    public function isValidStatusTransition($newStatus)
    {
        $currentStatus = $this->status;

        // Jika status sama, tidak perlu validasi
        if ($currentStatus === $newStatus) {
            return true;
        }

        // Bisa cancelled dari status manapun kecuali fully_received atau sudah cancelled
        if ($newStatus === 'cancelled') {
            return !in_array($currentStatus, ['fully_received', 'cancelled']);
        }

        // Tidak bisa update jika sudah fully_received atau cancelled
        if (in_array($currentStatus, ['fully_received', 'cancelled'])) {
            return false;
        }

        // Define valid transitions
        $validTransitions = [
            'draft' => ['sent', 'cancelled'],
            'sent' => ['confirmed', 'cancelled'],
            'confirmed' => ['partially_received', 'fully_received', 'cancelled'],
            'partially_received' => ['fully_received', 'cancelled'],
        ];

        return isset($validTransitions[$currentStatus]) &&
            in_array($newStatus, $validTransitions[$currentStatus]);
    }

    /**
     * Get total yang sudah dibayar
     */
    public function getTotalDibayarAttribute()
    {
        return $this->transaksiPembayaran()->sum('total_pembayaran');
    }

    /**
     * Get sisa pembayaran
     */
    public function getSisaPembayaranAttribute()
    {
        return $this->total_biaya - $this->total_dibayar;
    }

    /**
     * Check if DP sudah dibayar
     */
    public function isDpPaid()
    {
        return $this->transaksiPembayaran()->where('jenis_pembayaran', 'dp')->exists();
    }

    /**
     * Check if sudah lunas
     */
    public function isFullyPaid()
    {
        return $this->sisa_pembayaran <= 0;
    }

    // Scope Methods
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPemasok($query, $pemasokId)
    {
        return $query->where('pemasok_id', $pemasokId);
    }
}
