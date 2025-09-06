<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Pengiriman extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'pengiriman_id';
    protected $keyType = 'string';
    protected $table = 'pengiriman';
    public $incrementing = false;

    protected $fillable = [
        'pengiriman_id',
        'pesanan_id',
        'nomor_resi',
        'kurir',
        'biaya_pengiriman',
        'estimasi_hari',
        'status',
        'tanggal_kirim',
        'tanggal_diterima',
        'catatan',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'biaya_pengiriman' => 'decimal:2',
        'estimasi_hari' => 'integer',
        'tanggal_kirim' => 'date',
        'tanggal_diterima' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Auto-generate ID saat membuat record baru
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->pengiriman_id)) {
                $model->pengiriman_id = self::generateId();
            }
            $model->created_by = Auth::id();
        });

        static::updating(function ($model) {
            $model->updated_by = Auth::id();
        });

        static::deleting(function ($model) {
            $model->updated_by = Auth::id();
            $model->deleted_by = Auth::id();
        });
    }

    public static function generateId(): string
    {
        $lastId = self::where('pengiriman_id', 'LIKE', 'PG%')
            ->orderBy('pengiriman_id', 'desc')
            ->first();

        if (!$lastId) {
            return 'PG001';
        }

        $lastNumber = (int) substr($lastId->pengiriman_id, 2);
        $newNumber = $lastNumber + 1;

        return 'PG' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    // Status Methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isShipped(): bool
    {
        return $this->status === 'shipped';
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu Dikirim',
            'shipped' => 'Dikirim',
            'delivered' => 'Terkirim',
            'cancelled' => 'Dibatalkan',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'shipped' => 'bg-blue-100 text-blue-800',
            'delivered' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    // Relationships
    public function pesanan()
    {
        return $this->belongsTo(Pesanan::class, 'pesanan_id', 'pesanan_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'user_id');
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByKurir($query, $kurir)
    {
        return $query->where('kurir', $kurir);
    }

    // Static Methods untuk dropdown options
    public static function getKurirOptions(): array
    {
        return [
            'JNE' => 'JNE',
            'J&T' => 'J&T',
            'TIKI' => 'TIKI',
            'POS Indonesia' => 'POS Indonesia',
            'SiCepat' => 'SiCepat',
            'AnterAja' => 'AnterAja',
            'Gojek' => 'Gojek',
        ];
    }

    public static function getStatusOptions(): array
    {
        return [
            'pending' => 'Menunggu Dikirim',
            'shipped' => 'Dikirim',
            'delivered' => 'Terkirim',
            'cancelled' => 'Dibatalkan',
        ];
    }
}
