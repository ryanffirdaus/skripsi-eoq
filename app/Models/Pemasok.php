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
        'telepon',
        'alamat',
        'status',
        'catatan',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->pemasok_id) {
                $latest = static::withTrashed()->orderBy('pemasok_id', 'desc')->first();
                $nextNumber = $latest ? (int)substr($latest->pemasok_id, 3) + 1 : 1;
                $model->pemasok_id = 'PMS' . str_pad($nextNumber, 7, '0', STR_PAD_LEFT);
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
    public function pengadaanDetail()
    {
        return $this->hasMany(PengadaanDetail::class, 'pemasok_id', 'pemasok_id');
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
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function penerimaanBahanBaku()
    {
        return $this->hasMany(PenerimaanBahanBaku::class, 'pemasok_id', 'pemasok_id');
    }
}
