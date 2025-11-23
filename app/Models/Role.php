<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'role_id';

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

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'role_id',
        'nama',
    ];

    /**
     * Boot function for the model.
     *
     * Generates a new unique string ID for the model on creation.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $latestRole = static::latest('role_id')->first();
            $nextId = $latestRole ? intval(substr($latestRole->role_id, 2)) + 1 : 1;
            $model->role_id = 'RL' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Get the users for the role.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id', 'role_id');
    }
}
