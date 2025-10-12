<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PenerimaanBahanBaku extends Model
{
    use HasFactory;

    protected $primaryKey = 'penerimaan_id';
    protected $keyType = 'string';
    protected $table = 'penerimaan_bahan_baku';
    public $incrementing = false;

    protected $fillable = [
        'penerimaan_id',
        'pembelian_detail_id',
        'qty_diterima',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal_penerimaan' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->getKey()) {
                $latest = static::orderBy('penerimaan_id', 'desc')->first();
                $nextNumber = $latest ? (int)substr($latest->penerimaan_id, 3) + 1 : 1;
                $model->{$model->getKeyName()} = 'RBM' . str_pad($nextNumber, 7, '0', STR_PAD_LEFT);
            }
            if (Auth::check() && !$model->created_by) {
                $model->created_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check() && !$model->updated_by) {
                $model->updated_by = Auth::id();
            }
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
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    // Relasi ke user yang mengubah
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'user_id');
    }
}
