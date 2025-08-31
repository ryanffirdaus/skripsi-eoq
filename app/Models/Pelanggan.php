<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Pelanggan extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'pelanggan_id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'pelanggan_id',
        'email_pelanggan',
        'nama_pelanggan',
        'nomor_telepon',
        'alamat_pembayaran',
        'alamat_pengiriman',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Generate the next ID if not provided
            if (!$model->pelanggan_id) {
                $latest = static::orderBy('pelanggan_id', 'desc')->first();
                $nextId = $latest ? (int) substr($latest->pelanggan_id, 2) + 1 : 1;
                $model->pelanggan_id = 'CU' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
            }

            if (Auth::id()) {
                $model->created_by = Auth::id();
                $model->updated_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::id()) {
                $model->updated_by = Auth::id();
            }
        });

        static::deleting(function ($model) {
            if (Auth::id()) {
                $model->deleted_by = Auth::id();
            }
        });
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
}
