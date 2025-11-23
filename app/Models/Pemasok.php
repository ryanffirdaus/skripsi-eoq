<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Pemasok extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'pemasok_id';
    protected $keyType = 'string';
    protected $table = 'pemasok';
    public $incrementing = false;

    protected $fillable = [
        'pemasok_id',
        'nama_pemasok',
        'narahubung',
        'email',
        'nomor_telepon',
        'alamat',
        'catatan',
        'dibuat_oleh',
        'diubah_oleh',
        'dihapus_oleh'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->pemasok_id) {
                $latest = static::withTrashed()->orderBy('pemasok_id', 'desc')->first();
                $nextNumber = $latest ? (int)substr($latest->pemasok_id, 2) + 1 : 1;
                $model->pemasok_id = 'PM' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
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
        return $this->hasMany(PengadaanDetail::class, 'pemasok_id', 'pemasok_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh', 'user_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'diubah_oleh', 'user_id');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'dihapus_oleh', 'user_id');
    }

    // Status methods
    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isInactive()
    {
        return $this->status === 'inactive';
    }

    // Scope methods
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function scopeInactive($query)
    {
        return $query->onlyTrashed();
    }

    public function penerimaanBahanBaku()
    {
        return $this->hasMany(PenerimaanBahanBaku::class, 'pemasok_id', 'pemasok_id');
    }
}
