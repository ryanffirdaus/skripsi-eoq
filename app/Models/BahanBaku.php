<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class BahanBaku extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'bahan_baku_id';

    protected $keyType = 'string';

    protected $table = 'bahan_baku';

    public $incrementing = false;

    protected $fillable = [
        'bahan_baku_id',
        'nama_bahan',
        'deskripsi',
        'satuan',
        'stok_saat_ini',
        'stok_minimum',
        'safety_stock',
        'reorder_point',
        'lead_time',
        'demand_tahunan',
        'biaya_pemesanan',
        'biaya_penyimpanan',
        'eoq',
        'harga_per_unit',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Generate the next ID if not provided
            if (!$model->bahan_baku_id) {
                $latest = static::withTrashed()->orderBy('bahan_baku_id', 'desc')->first();
                $nextId = $latest ? (int) substr($latest->bahan_baku_id, 2) + 1 : 1;
                $model->bahan_baku_id = 'BB' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
            }
        });

        static::created(function ($model) {
            $model->created_by = Auth::id();
            $model->updated_by = Auth::id();
            $model->saveQuietly(); // Prevent triggering events again
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id();
            $model->saveQuietly(); // Prevent triggering events again
        });

        static::deleting(function ($model) {
            $model->deleted_by = Auth::id();
            $model->saveQuietly(); // Prevent triggering events again
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

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'bahan_baku_id';
    }
}
