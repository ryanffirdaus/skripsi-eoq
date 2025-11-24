<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class PenerimaanBahanBaku extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'penerimaan_id';
    protected $keyType = 'string';
    protected $table = 'penerimaan_bahan_baku';
    public $incrementing = false;

    protected $fillable = [
        'penerimaan_id',
        'pembelian_detail_id',
        'qty_diterima',
        'dibuat_oleh',
        'diubah_oleh',
        'dihapus_oleh',
    ];

    protected $casts = [
        'tanggal_penerimaan' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->getKey()) {
                $latest = static::withTrashed()->orderByRaw('CAST(SUBSTRING(penerimaan_id, 3) AS UNSIGNED) DESC')->first();
                $nextNumber = $latest ? (int)substr($latest->penerimaan_id, 2) + 1 : 1;
                $model->{$model->getKeyName()} = 'PN' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            }
            if (Auth::check() && !$model->dibuat_oleh) {
                $model->dibuat_oleh = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check() && !$model->diubah_oleh) {
                $model->diubah_oleh = Auth::id();
            }
        });

        static::deleting(function ($model) {
            if (Auth::check()) {
                $model->dihapus_oleh = Auth::id();
            }

            // Soft delete all detail items
            $model->detail()->each(function ($detail) {
                $detail->delete();
            });
        });
    }

    // Relasi ke detail penerimaan
    public function detail()
    {
        return $this->hasMany(PenerimaanBahanBakuDetail::class, 'penerimaan_id', 'penerimaan_id');
    }

    // Relasi ke pembelian detail (direct relationship)
    public function pembelianDetail()
    {
        return $this->belongsTo(PembelianDetail::class, 'pembelian_detail_id', 'pembelian_detail_id');
    }

    // Relasi ke pembelian melalui pembelianDetail
    public function pembelian()
    {
        return $this->hasOneThrough(
            Pembelian::class,
            PembelianDetail::class,
            'pembelian_detail_id', // Foreign key on PembelianDetail table
            'pembelian_id', // Foreign key on Pembelian table
            'pembelian_detail_id', // Local key on PenerimaanBahanBaku table
            'pembelian_id' // Local key on PembelianDetail table
        );
    }

    // Relasi ke user yang membuat
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh', 'user_id');
    }

    // Relasi ke user yang mengubah
    public function diubahOleh()
    {
        return $this->belongsTo(User::class, 'diubah_oleh', 'user_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'diubah_oleh', 'user_id');
    }

    // Relasi ke user yang menghapus
    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'dihapus_oleh', 'user_id');
    }
}
