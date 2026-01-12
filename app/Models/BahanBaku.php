<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\InventoryCalculationService;


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
        'stok_bahan',
        'satuan_bahan',
        'lokasi_bahan',
        'harga_bahan',
        'dibuat_oleh',
        'diubah_oleh',
        'dihapus_oleh'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Generate the next ID if not provided
            if (!$model->bahan_baku_id) {
                $latest = static::withTrashed()->orderByRaw('CAST(SUBSTRING(bahan_baku_id, 3) AS UNSIGNED) DESC')->first();
                $nextId = $latest ? (int) substr($latest->bahan_baku_id, 2) + 1 : 1;
                $model->bahan_baku_id = 'BB' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
            }
        });

        static::created(function ($model) {
            $model->dibuat_oleh = Auth::id();
            $model->diubah_oleh = Auth::id();
            $model->saveQuietly(); // Prevent triggering events again
        });

        static::updating(function ($model) {
            $model->diubah_oleh = Auth::id();
            $model->saveQuietly(); // Prevent triggering events again
        });

        static::deleting(function ($model) {
            $model->dihapus_oleh = Auth::id();
            $model->saveQuietly(); // Prevent triggering events again
        });
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

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'bahan_baku_id';
    }

    /**
     * Accessor untuk EOQ - kalkulasi dinamis
     */
    public function getEoqBahanAttribute()
    {
        try {
            $service = app(InventoryCalculationService::class);
            $metrics = $service->calculateBahanBakuMetrics($this->bahan_baku_id);
            return $metrics['eoq'];
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Accessor untuk ROP - kalkulasi dinamis
     */
    public function getRopBahanAttribute()
    {
        try {
            $service = app(InventoryCalculationService::class);
            $metrics = $service->calculateBahanBakuMetrics($this->bahan_baku_id);
            return $metrics['rop'];
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Accessor untuk Safety Stock - kalkulasi dinamis
     */
    public function getSafetyStockBahanAttribute()
    {
        try {
            $service = app(InventoryCalculationService::class);
            $metrics = $service->calculateBahanBakuMetrics($this->bahan_baku_id);
            return $metrics['safety_stock'];
        } catch (\Exception $e) {
            return 0;
        }
    }
}
