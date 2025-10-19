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
    protected $table = 'penugasan_produksi';

    protected $fillable = [
        'pengadaan_detail_id',
        'user_id',
        'jumlah_produksi',
        'status',
        'deadline',
        'catatan',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'deadline' => 'date',
        'jumlah_produksi' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
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
        return $this->belongsTo(PengadaanDetail::class, 'pengadaan_detail_id', 'pengadaan_detail_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
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

    // Status Methods
    public function isAssigned()
    {
        return $this->status === 'assigned';
    }

    public function isInProgress()
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    // Status Transition Validation
    public function isValidStatusTransition($newStatus)
    {
        $currentStatus = $this->status;

        if ($currentStatus === $newStatus) {
            return true;
        }

        $validTransitions = [
            'assigned' => ['in_progress', 'cancelled'],
            'in_progress' => ['completed', 'cancelled'],
            'completed' => [],
            'cancelled' => [],
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
        return $query->whereIn('status', ['assigned', 'in_progress']);
    }
}
