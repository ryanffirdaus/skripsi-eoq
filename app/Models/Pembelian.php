<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pembelian extends Model
{
    use HasFactory;

    protected $table = 'pembelian';
    protected $primaryKey = 'pembelian_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'pembelian_id',
        'pengadaan_id',
        'supplier_id',
        'nomor_po',
        'tanggal_pembelian',
        'tanggal_jatuh_tempo',
        'subtotal',
        'pajak',
        'diskon',
        'total_biaya',
        'status',
        'metode_pembayaran',
        'terms_conditions',
        'catatan',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tanggal_pembelian' => 'date',
        'tanggal_jatuh_tempo' => 'date',
        'subtotal' => 'decimal:2',
        'pajak' => 'decimal:2',
        'diskon' => 'decimal:2',
        'total_biaya' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->pembelian_id)) {
                $model->pembelian_id = 'PO-' . date('Ymd') . '-' . str_pad(
                    (static::whereDate('created_at', today())->count() + 1),
                    3,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }

    // Relationships
    public function pengadaan(): BelongsTo
    {
        return $this->belongsTo(Pengadaan::class, 'pengadaan_id', 'pengadaan_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function detail(): HasMany
    {
        return $this->hasMany(PembelianDetail::class, 'pembelian_id', 'pembelian_id');
    }

    public function pembelianDetails(): HasMany
    {
        return $this->hasMany(PembelianDetail::class, 'pembelian_id', 'pembelian_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'user_id');
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeBySupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('tanggal_pembelian', [$startDate, $endDate]);
    }

    // Accessors & Mutators
    public function getStatusLabelAttribute()
    {
        $labels = [
            'draft' => 'Draft',
            'sent' => 'PO Dikirim',
            'confirmed' => 'Dikonfirmasi',
            'received' => 'Diterima',
            'invoiced' => 'Ditagih',
            'paid' => 'Dibayar',
            'cancelled' => 'Dibatalkan',
        ];

        return $labels[$this->status] ?? $this->status;
    }

    public function getFormattedTotalAttribute()
    {
        return 'Rp ' . number_format($this->total_biaya, 0, ',', '.');
    }

    // Business Logic Methods
    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'sent']);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'sent', 'confirmed']);
    }

    public function canBeReceived(): bool
    {
        return $this->status === 'confirmed';
    }

    public function isFullyReceived(): bool
    {
        return $this->detail->every(function ($item) {
            return $item->qty_diterima >= $item->qty_po;
        });
    }

    public function getReceivedPercentage(): int
    {
        $totalPo = $this->detail->sum('qty_po');
        $totalReceived = $this->detail->sum('qty_diterima');

        return $totalPo > 0 ? round(($totalReceived / $totalPo) * 100) : 0;
    }

    public function updateStatus($newStatus): bool
    {
        $allowedTransitions = [
            'draft' => ['sent', 'cancelled'],
            'sent' => ['confirmed', 'cancelled'],
            'confirmed' => ['received', 'cancelled'],
            'received' => ['invoiced'],
            'invoiced' => ['paid'],
        ];

        if (
            !isset($allowedTransitions[$this->status]) ||
            !in_array($newStatus, $allowedTransitions[$this->status])
        ) {
            return false;
        }

        $this->status = $newStatus;
        return $this->save();
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->detail->sum('total_harga');
        $this->total_biaya = $this->subtotal + $this->pajak - $this->diskon;
        $this->save();
    }
}
