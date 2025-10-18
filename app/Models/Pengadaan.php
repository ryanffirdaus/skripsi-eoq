<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Pengadaan extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'pengadaan_id';
    protected $keyType = 'string';
    protected $table = 'pengadaan';
    public $incrementing = false;

    protected $fillable = [
        'pengadaan_id',
        'jenis_pengadaan',
        'pesanan_id',
        'status',
        'catatan',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $appends = ['total_biaya'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->pengadaan_id) {
                $latest = static::withTrashed()->orderBy('pengadaan_id', 'desc')->first();
                $nextNumber = $latest ? (int)substr($latest->pengadaan_id, 3) + 1 : 1;
                $model->pengadaan_id = 'PGD' . str_pad($nextNumber, 7, '0', STR_PAD_LEFT);
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
        });
    }

    // Relationships

    public function pesanan()
    {
        return $this->belongsTo(Pesanan::class, 'pesanan_id', 'pesanan_id');
    }

    public function detail()
    {
        return $this->hasMany(PengadaanDetail::class, 'pengadaan_id', 'pengadaan_id');
    }

    public function pengadaanDetails()
    {
        return $this->hasMany(PengadaanDetail::class, 'pengadaan_id', 'pengadaan_id');
    }

    public function pembelian()
    {
        return $this->hasMany(Pembelian::class, 'pengadaan_id', 'pengadaan_id');
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

    // Accessors
    public function getTotalBiayaAttribute()
    {
        return $this->detail->sum(function ($detail) {
            return $detail->total_harga;
        });
    }

    // Status methods (SOURCE OF TRUTH: migration)
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isDisetujuiProcurement()
    {
        return $this->status === 'disetujui_procurement';
    }

    public function isDisetujuiFinance()
    {
        return $this->status === 'disetujui_finance';
    }

    public function isDiproses()
    {
        return $this->status === 'diproses';
    }

    public function isDiterima()
    {
        return $this->status === 'diterima';
    }

    public function isDibatalkan()
    {
        return $this->status === 'dibatalkan';
    }

    // Business logic methods
    public function canBeEdited()
    {
        // Edit button tampil untuk semua status kecuali dibatalkan dan diterima
        return !in_array($this->status, ['dibatalkan', 'diterima']);
    }

    public function canBeCancelled()
    {
        return !in_array($this->status, ['diterima', 'dibatalkan']);
    }

    /**
     * Validasi apakah status transition valid
     * Flow: pending → disetujui_procurement/ditolak_procurement → disetujui_finance/ditolak_finance → diproses → diterima
     * Bisa dibatalkan dari status manapun kecuali diterima
     */
    public function isValidStatusTransition($newStatus)
    {
        $currentStatus = $this->status;

        // Jika status sama, tidak perlu validasi
        if ($currentStatus === $newStatus) {
            return true;
        }

        // Bisa dibatalkan dari status manapun kecuali diterima atau sudah dibatalkan
        if ($newStatus === 'dibatalkan') {
            return !in_array($currentStatus, ['diterima', 'dibatalkan']);
        }

        // Tidak bisa update jika sudah diterima atau dibatalkan
        if (in_array($currentStatus, ['diterima', 'dibatalkan'])) {
            return false;
        }

        // Define valid transitions
        $validTransitions = [
            'pending' => ['disetujui_procurement', 'ditolak_procurement', 'dibatalkan'],
            'ditolak_procurement' => ['disetujui_procurement', 'dibatalkan'],
            'disetujui_procurement' => ['disetujui_finance', 'ditolak_finance', 'dibatalkan'],
            'ditolak_finance' => ['disetujui_finance', 'dibatalkan'],
            'disetujui_finance' => ['diproses', 'dibatalkan'],
            'diproses' => ['diterima', 'dibatalkan'],
        ];

        return isset($validTransitions[$currentStatus]) &&
            in_array($newStatus, $validTransitions[$currentStatus]);
    }

    public function updateTotalBiaya()
    {
        // Total biaya is now calculated as an accessor from detail->total_harga
        // This method is kept for backward compatibility but does nothing
        return $this->total_biaya;
    }

    // Scope methods
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByJenisPengadaan($query, $jenis)
    {
        return $query->where('jenis_pengadaan', $jenis);
    }

    public function penugasan()
    {
        return $this->hasMany(PenugasanProduksi::class, 'pengadaan_id', 'pengadaan_id');
    }
}
