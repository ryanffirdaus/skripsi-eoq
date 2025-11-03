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
        'alasan_penolakan',
        'rejected_by',
        'rejected_at',
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

            // Soft delete all detail items
            $model->detail()->each(function ($detail) {
                $detail->delete();
            });
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

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by', 'user_id');
    }

    // Accessors
    public function getTotalBiayaAttribute()
    {
        return $this->detail->sum(function ($detail) {
            return $detail->total_harga;
        });
    }

    // Status constants (SOURCE OF TRUTH: migration)
    // Flow: draft → pending_approval_gudang → pending_supplier_allocation → pending_approval_pengadaan → pending_approval_keuangan → processed → received
    // At any stage: can be rejected (status = rejected)
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_APPROVAL_GUDANG = 'pending_approval_gudang'; // Menunggu approval Manajer Gudang
    public const STATUS_PENDING_SUPPLIER_ALLOCATION = 'pending_supplier_allocation'; // Menunggu diisi pemasok
    public const STATUS_PENDING_APPROVAL_PENGADAAN = 'pending_approval_pengadaan'; // Menunggu approval Manajer Pengadaan
    public const STATUS_PENDING_APPROVAL_KEUANGAN = 'pending_approval_keuangan'; // Menunggu approval Manajer Keuangan
    public const STATUS_PROCESSED = 'processed'; // Sudah disetujui keuangan, siap di-PO
    public const STATUS_RECEIVED = 'received'; // Barang diterima
    public const STATUS_CANCELLED = 'cancelled'; // Dibatalkan
    public const STATUS_REJECTED = 'rejected'; // Ditolak (harus mengisi alasan penolakan)

    // Status methods (SOURCE OF TRUTH: migration)
    public function isDraft()
    {
        return $this->status === 'draft';
    }

    public function isPendingApprovalGudang()
    {
        return $this->status === 'pending_approval_gudang';
    }

    public function isPendingSupplierAllocation()
    {
        return $this->status === 'pending_supplier_allocation';
    }

    public function isPendingApprovalPengadaan()
    {
        return $this->status === 'pending_approval_pengadaan';
    }

    public function isPendingApprovalKeuangan()
    {
        return $this->status === 'pending_approval_keuangan';
    }

    public function isProcessed()
    {
        return $this->status === 'processed';
    }

    public function isReceived()
    {
        return $this->status === 'received';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    // Business logic methods
    public function canBeEdited()
    {
        $user = Auth::user();

        // Admin (R01) dapat edit di SEMUA status tanpa exception
        if ($user && $user->role_id === 'R01') {
            return true;
        }

        // Bisa edit di tahap: pending, disetujui_gudang
        // Tidak bisa edit setelah disetujui_pengadaan, disetujui_keuangan, diproses, diterima, dibatalkan
        return in_array($this->status, ['pending', 'disetujui_gudang']);
    }

    public function canBeCancelled()
    {
        return !in_array($this->status, ['diterima', 'dibatalkan']);
    }

    /**
     * Check apakah pengadaan hanya berisi bahan_baku
     */
    public function hasBahanBakuOnly(): bool
    {
        $types = $this->detail->pluck('jenis_barang')->unique();
        return $types->count() === 1 && $types->first() === 'bahan_baku';
    }

    /**
     * Check apakah pengadaan hanya berisi produk
     */
    public function hasProdukOnly(): bool
    {
        $types = $this->detail->pluck('jenis_barang')->unique();
        return $types->count() === 1 && $types->first() === 'produk';
    }

    /**
     * Check apakah pengadaan mixed (bahan_baku + produk)
     */
    public function isMixed(): bool
    {
        $types = $this->detail->pluck('jenis_barang')->unique();
        return $types->count() > 1;
    }

    /**
     * Get jenis_barang items dalam pengadaan
     */
    public function getItemTypes(): array
    {
        return $this->detail
            ->pluck('jenis_barang')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Validasi apakah status transition valid
     * Flow: draft → pending_approval_gudang → pending_supplier_allocation → pending_approval_pengadaan → pending_approval_keuangan → processed → received
     * Bisa rejected dari status manapun kecuali received atau sudah rejected
     * Bisa cancelled dari status manapun kecuali received atau sudah cancelled
     */
    public function isValidStatusTransition($newStatus)
    {
        $currentStatus = $this->status;

        // Jika status sama, tidak perlu validasi
        if ($currentStatus === $newStatus) {
            return true;
        }

        // Bisa rejected dari status manapun kecuali received atau sudah rejected
        if ($newStatus === 'rejected') {
            return !in_array($currentStatus, ['received', 'rejected']);
        }

        // Bisa cancelled dari status manapun kecuali received atau sudah cancelled
        if ($newStatus === 'cancelled') {
            return !in_array($currentStatus, ['received', 'cancelled', 'rejected']);
        }

        // Tidak bisa update jika sudah received, cancelled, atau rejected
        if (in_array($currentStatus, ['received', 'cancelled', 'rejected'])) {
            return false;
        }

        // Define valid transitions (WORKFLOW)
        $validTransitions = [
            'draft' => ['pending_approval_gudang', 'cancelled'],
            'pending_approval_gudang' => ['pending_supplier_allocation', 'cancelled'],
            'pending_supplier_allocation' => ['pending_approval_pengadaan', 'cancelled'],
            'pending_approval_pengadaan' => ['pending_approval_keuangan', 'cancelled'],
            'pending_approval_keuangan' => ['processed', 'cancelled'],
            'processed' => ['received', 'cancelled'],
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

    /**
     * Reject pengadaan dengan alasan penolakan
     */
    public function reject($reason)
    {
        $this->update([
            'status' => 'rejected',
            'alasan_penolakan' => $reason,
            'rejected_by' => Auth::user()->user_id,
            'rejected_at' => now(),
        ]);

        return $this;
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
