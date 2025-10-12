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

    protected $casts = [
        'tanggal_pengadaan' => 'date',
        'tanggal_delivery' => 'date',
        'total_biaya' => 'decimal:2',
    ];

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

    // Status methods
    public function isDraft()
    {
        return $this->status === 'draft';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isOrdered()
    {
        return $this->status === 'ordered';
    }

    public function isReceived()
    {
        return $this->status === 'received';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    // Business logic methods
    public function canBeEdited()
    {
        return in_array($this->status, ['draft', 'pending']);
    }

    public function canBeCancelled()
    {
        return !in_array($this->status, ['received', 'cancelled']);
    }

    public function updateTotalBiaya()
    {
        $this->total_biaya = $this->detail()->sum('total_harga');
        $this->save();
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
