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
        'pengadaan_id',
        'assigned_to',
        'assigned_by',
        'qty_assigned',
        'qty_completed',
        'status',
        'deadline',
        'catatan',
    ];

    protected $casts = [
        'deadline' => 'date',
    ];

    public function pengadaan()
    {
        return $this->belongsTo(Pengadaan::class, 'pengadaan_id', 'pengadaan_id');
    }

    public function assignedToUser()
    {
        return $this->belongsTo(User::class, 'assigned_to', 'user_id');
    }

    public function assignedByUser()
    {
        return $this->belongsTo(User::class, 'assigned_by', 'user_id');
    }

    public function getProgressPercentageAttribute()
    {
        if ($this->qty_assigned == 0) return 0;
        return round(($this->qty_completed / $this->qty_assigned) * 100, 2);
    }
}
