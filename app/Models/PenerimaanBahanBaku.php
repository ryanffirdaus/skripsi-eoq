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
        'pembelian_id',
        'pemasok_id',
        'nomor_penerimaan',
        'nomor_surat_jalan',
        'tanggal_penerimaan',
        'status',
        'catatan',
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
            if (!$model->nomor_penerimaan) {
                $yearMonth = date('Ym');
                $latestInMonth = static::where('nomor_penerimaan', 'like', "RCV-{$yearMonth}-%")->count();
                $nextNumberInMonth = $latestInMonth + 1;
                $model->nomor_penerimaan = "RCV-" . $yearMonth . "-" . str_pad($nextNumberInMonth, 4, '0', STR_PAD_LEFT);
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

    // Relasi ke pembelian (PO) asal
    public function pembelian()
    {
        return $this->belongsTo(Pembelian::class, 'pembelian_id', 'pembelian_id');
    }

    // Relasi ke pemasok
    public function pemasok()
    {
        return $this->belongsTo(Pemasok::class, 'pemasok_id', 'pemasok_id');
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
