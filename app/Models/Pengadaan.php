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
        'ditolak_oleh',
        'dibuat_oleh',
        'diubah_oleh',
        'dihapus_oleh'
    ];

    protected $appends = ['total_biaya'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->pengadaan_id) {
                $latest = static::withTrashed()->orderBy('pengadaan_id', 'desc')->first();
                $nextNumber = $latest ? (int)substr($latest->pengadaan_id, 2) + 1 : 1;
                $model->pengadaan_id = 'PG' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }

            if (Auth::check()) {
                $model->dibuat_oleh = Auth::user()->user_id;
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->diubah_oleh = Auth::user()->user_id;
            }
        });

        static::deleting(function ($model) {
            if (Auth::check()) {
                $model->dihapus_oleh = Auth::user()->user_id;
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
        return $this->belongsTo(User::class, 'dibuat_oleh', 'user_id');
    }

    public function diubahOleh()
    {
        return $this->belongsTo(User::class, 'diubah_oleh', 'user_id');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'dihapus_oleh', 'user_id');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'ditolak_oleh', 'user_id');
    }

    // Accessors
    public function getTotalBiayaAttribute()
    {
        return $this->detail->sum(function ($detail) {
            return $detail->total_harga;
        });
    }

    // Status constants (SOURCE OF TRUTH: migration)
    // Flow: draft → menunggu_persetujuan_gudang → menunggu_alokasi_pemasok → menunggu_persetujuan_pengadaan → menunggu_persetujuan_keuangan → diproses → diterima
    // At any stage: can be rejected (status = rejected)
    public const STATUS_DRAFT = 'draft';
    public const STATUS_MENUNGGU_PERSETUJUAN_GUDANG = 'menunggu_persetujuan_gudang'; // Menunggu approval Manajer Gudang
    public const STATUS_MENUNGGU_ALOKASI_PEMASOK = 'menunggu_alokasi_pemasok'; // Menunggu diisi pemasok
    public const STATUS_MENUNGGU_PERSETUJUAN_PENGADAAN = 'menunggu_persetujuan_pengadaan'; // Menunggu approval Manajer Pengadaan
    public const STATUS_MENUNGGU_PERSETUJUAN_KEUANGAN = 'menunggu_persetujuan_keuangan'; // Menunggu approval Manajer Keuangan
    public const STATUS_DIPROSES = 'diproses'; // Sudah disetujui keuangan, siap di-PO
    public const STATUS_DITERIMA = 'diterima'; // Barang diterima
    public const STATUS_DIBATALKAN = 'dibatalkan'; // Dibatalkan
    public const STATUS_DITOLAK = 'ditolak'; // Ditolak (harus mengisi alasan penolakan)

    // Status methods (SOURCE OF TRUTH: migration)
    public function isDraft()
    {
        return $this->status === 'draft';
    }

    public function isMenungguPersetujuanGudang()
    {
        return $this->status === 'menunggu_persetujuan_gudang';
    }

    public function isMenungguAlokasiPemasok()
    {
        return $this->status === 'menunggu_alokasi_pemasok';
    }

    public function isMenungguPersetujuanPengadaan()
    {
        return $this->status === 'menunggu_persetujuan_pengadaan';
    }

    public function isMenungguPersetujuanKeuangan()
    {
        return $this->status === 'menunggu_persetujuan_keuangan';
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

    public function isDitolak()
    {
        return $this->status === 'ditolak';
    }

    // Business logic methods
    public function canBeEdited()
    {
        $user = Auth::user();

        // Admin (R01) dapat edit di SEMUA status tanpa exception
        if ($user && $user->role_id === 'R01') {
            return true;
        }

        // Bisa edit di tahap: draft, menunggu_alokasi_pemasok (untuk input pemasok/harga)
        // Tidak bisa edit setelah menunggu_persetujuan_pengadaan, menunggu_persetujuan_keuangan, diproses, diterima, dibatalkan
        return in_array($this->status, ['draft', 'menunggu_persetujuan_gudang', 'menunggu_alokasi_pemasok']);
    }

    public function canBeCancelled()
    {
        return !in_array($this->status, ['diterima', 'dibatalkan', 'ditolak']);
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
     * Flow: draft → menunggu_persetujuan_gudang → menunggu_alokasi_pemasok → menunggu_persetujuan_pengadaan → menunggu_persetujuan_keuangan → diproses → diterima
     * Bisa ditolak dari status manapun kecuali diterima atau sudah ditolak
     * Bisa dibatalkan dari status manapun kecuali diterima atau sudah dibatalkan
     */
    public function isValidStatusTransition($newStatus)
    {
        $currentStatus = $this->status;

        // Jika status sama, tidak perlu validasi
        if ($currentStatus === $newStatus) {
            return true;
        }

        // Bisa ditolak dari status manapun kecuali diterima atau sudah ditolak
        if ($newStatus === 'ditolak') {
            return !in_array($currentStatus, ['diterima', 'ditolak']);
        }

        // Bisa dibatalkan dari status manapun kecuali diterima atau sudah dibatalkan
        if ($newStatus === 'dibatalkan') {
            return !in_array($currentStatus, ['diterima', 'dibatalkan', 'ditolak']);
        }

        // Tidak bisa update jika sudah diterima, dibatalkan, atau ditolak
        if (in_array($currentStatus, ['diterima', 'dibatalkan', 'ditolak'])) {
            return false;
        }

        // Define valid transitions (WORKFLOW)
        $validTransitions = [
            'draft' => ['menunggu_persetujuan_gudang', 'dibatalkan'],
            'menunggu_persetujuan_gudang' => ['menunggu_alokasi_pemasok', 'dibatalkan'],
            'menunggu_alokasi_pemasok' => ['menunggu_persetujuan_pengadaan', 'dibatalkan'],
            'menunggu_persetujuan_pengadaan' => ['menunggu_persetujuan_keuangan', 'dibatalkan'],
            'menunggu_persetujuan_keuangan' => ['diproses', 'dibatalkan'],
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

    /**
     * Reject pengadaan dengan alasan penolakan
     */
    public function reject($reason)
    {
        $this->update([
            'status' => 'ditolak',
            'alasan_penolakan' => $reason,
            'ditolak_oleh' => Auth::user()->user_id,
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
