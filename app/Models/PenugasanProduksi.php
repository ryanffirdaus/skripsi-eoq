<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class PenugasanProduksi extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'penugasan_id';

    protected $keyType = 'string';

    protected $table = 'penugasan_produksi';

    public $incrementing = false;

    protected $fillable = [
        'penugasan_id',
        'pengadaan_detail_id',
        'user_id',
        'jumlah_produksi',
        'status',
        'deadline',
        'catatan',
        'dibuat_oleh',
        'diubah_oleh',
        'dihapus_oleh',
    ];

    protected $casts = [
        'deadline' => 'date',
        'jumlah_produksi' => 'integer',
    ];

    protected $with = [
        'pengadaanDetail',
        'user',
        'createdBy',
    ];

    protected $appends = [
        'created_by_user',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Auto-generate penugasan_id
            if (!$model->penugasan_id) {
                $latest = static::withTrashed()->orderBy('penugasan_id', 'desc')->first();
                $nextId = $latest ? (int) substr($latest->penugasan_id, 2) + 1 : 1;
                $model->penugasan_id = 'PN' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
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
        });
    }

    // Relationships

    public function pengadaanDetail()
    {
        return $this->belongsTo(PengadaanDetail::class, 'pengadaan_detail_id', 'pengadaan_detail_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh', 'user_id');
    }

    public function diubahOleh()
    {
        return $this->belongsTo(User::class, 'diubah_oleh', 'user_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'diubah_oleh', 'user_id');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'dihapus_oleh', 'user_id');
    }

    // Status Methods
    public function isDitugaskan()
    {
        return $this->status === 'ditugaskan';
    }

    public function isProses()
    {
        return $this->status === 'proses';
    }

    public function isSelesai()
    {
        return $this->status === 'selesai';
    }

    public function isDibatalkan()
    {
        return $this->status === 'dibatalkan';
    }

    // Status Transition Validation
    public function isValidStatusTransition($newStatus)
    {
        $currentStatus = $this->status;

        if ($currentStatus === $newStatus) {
            return true;
        }

        $validTransitions = [
            'ditugaskan' => ['proses', 'dibatalkan'],
            'proses' => ['selesai', 'dibatalkan'],
            'selesai' => [],
            'dibatalkan' => [],
        ];

        return isset($validTransitions[$currentStatus]) &&
            in_array($newStatus, $validTransitions[$currentStatus]);
    }

    // Scope methods
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByPengadaanDetail($query, $pengadaanDetailId)
    {
        return $query->where('pengadaan_detail_id', $pengadaanDetailId);
    }

    public function scopeOutstanding($query)
    {
        return $query->whereIn('status', ['ditugaskan', 'proses']);
    }

    // Accessor untuk memastikan created_by_user selalu tersedia
    public function getCreatedByUserAttribute()
    {
        return $this->createdBy;
    }
}
