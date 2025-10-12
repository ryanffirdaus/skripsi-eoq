<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class MasterProduk extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'master_produk_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $table = 'master_produk';

    protected $fillable = [
        'master_produk_id',
        'penugasan_id', // Foreign key ke Penugasan Produksi
        'produk_id',    // Foreign key ke Produk (barang jadi)
        'status',       // Enum: 'Pending QC', 'Good', 'Reject', 'Ready'
        'qc_by',        // Foreign key ke User (Staf QA)
        'qc_at',        // Waktu saat QC dilakukan
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'qc_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::user()->user_id;
            }
        });
    }

    // Relasi ke penugasan yang menghasilkan produk ini
    public function penugasan()
    {
        return $this->belongsTo(PenugasanProduksi::class, 'penugasan_id', 'penugasan_produksi_id');
    }

    // Relasi ke data master produknya (untuk mendapatkan nama, dll.)
    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id', 'produk_id');
    }

    // Relasi untuk mengetahui siapa Staf QA yang melakukan QC
    public function qcBy()
    {
        return $this->belongsTo(User::class, 'qc_by', 'user_id');
    }

    // Relasi untuk tracking user
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'user_id');
    }
}
