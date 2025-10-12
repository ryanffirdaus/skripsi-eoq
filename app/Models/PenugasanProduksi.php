<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class PenugasanProduksi extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'penugasan_produksi_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $table = 'penugasan_produksi';

    protected $fillable = [
        'penugasan_produksi_id',
        'pengadaan_id', // Foreign key ke tabel Pengadaan
        'staf_id',      // Foreign key ke tabel User (Staf RnD)
        'jumlah_produksi',
        'jumlah_telah_diproduksi',
        'status', // Enum: 'Ditugaskan', 'Berjalan', 'Selesai'
        'catatan',
        'created_by',
        'updated_by',
        'deleted_by',
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

    // RELASI UTAMA: Menghubungkan penugasan ini ke sumber permintaannya.
    public function pengadaan()
    {
        return $this->belongsTo(Pengadaan::class, 'pengadaan_id', 'pengadaan_id');
    }

    // RELASI UTAMA: Menentukan siapa staf yang bertanggung jawab.
    public function staf()
    {
        return $this->belongsTo(User::class, 'staf_id', 'user_id');
    }

    // Relasi untuk melacak hasil produksi dari penugasan ini.
    public function masterProduk()
    {
        return $this->hasMany(MasterProduk::class, 'penugasan_id', 'penugasan_produksi_id');
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
