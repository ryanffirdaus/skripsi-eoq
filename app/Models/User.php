<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'user_id';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'nama_lengkap',
        'email',
        'password',
        'role_id',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Generate the next ID if not provided
            if (!$model->user_id) {
                $latest = static::withTrashed()->orderBy('user_id', 'desc')->first();
                $nextId = $latest ? (int) substr($latest->user_id, 2) + 1 : 1;
                $model->user_id = 'US' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
            }
        });

        static::created(function ($model) {
            $model->created_by = Auth::id() ?? null;
            $model->updated_by = Auth::id() ?? null;
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

    /**
     * Get the role associated with the user.
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }

    public function bahanBakuCreatedBy()
    {
        return $this->hasMany(BahanBaku::class, 'created_by', 'user_id');
    }

    public function bahanBakuUpdatedBy()
    {
        return $this->hasMany(BahanBaku::class, 'updated_by', 'user_id');
    }

    public function bahanBakuDeletedBy()
    {
        return $this->hasMany(BahanBaku::class, 'deleted_by', 'user_id');
    }

    public function produkCreatedBy()
    {
        return $this->hasMany(Produk::class, 'created_by', 'user_id');
    }

    public function produkUpdatedBy()
    {
        return $this->hasMany(Produk::class, 'updated_by', 'user_id');
    }

    public function produkDeletedBy()
    {
        return $this->hasMany(Produk::class, 'deleted_by', 'user_id');
    }

    public function pelangganCreatedBy()
    {
        return $this->hasMany(Pelanggan::class, 'created_by', 'user_id');
    }

    public function pelangganUpdatedBy()
    {
        return $this->hasMany(Pelanggan::class, 'updated_by', 'user_id');
    }

    public function pelangganDeletedBy()
    {
        return $this->hasMany(Pelanggan::class, 'deleted_by', 'user_id');
    }

    public function pesananCreatedBy()
    {
        return $this->hasMany(Pesanan::class, 'created_by', 'user_id');
    }

    public function pesananUpdatedBy()
    {
        return $this->hasMany(Pesanan::class, 'updated_by', 'user_id');
    }

    public function pesananDeletedBy()
    {
        return $this->hasMany(Pesanan::class, 'deleted_by', 'user_id');
    }

    // Self-referencing relationships for Users
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'user_id');
    }

    // Relationships for PenugasanProduksi (Production Assignment)
    // Penugasan yang ditugaskan kepada user ini
    public function penugasanProduksi()
    {
        return $this->hasMany(PenugasanProduksi::class, 'user_id', 'user_id');
    }

    // Penugasan yang dibuat/ditugaskan oleh user ini
    public function penugasanCreated()
    {
        return $this->hasMany(PenugasanProduksi::class, 'created_by', 'user_id');
    }

    // Penugasan yang di-update oleh user ini
    public function penugasanUpdated()
    {
        return $this->hasMany(PenugasanProduksi::class, 'updated_by', 'user_id');
    }

    // Penugasan yang dihapus oleh user ini
    public function penugasanDeleted()
    {
        return $this->hasMany(PenugasanProduksi::class, 'deleted_by', 'user_id');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'user_id';
    }
}
