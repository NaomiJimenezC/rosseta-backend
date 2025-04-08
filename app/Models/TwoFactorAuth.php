<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TwoFactorAuth extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'is_enabled',
        'method',
        'secret_key',
        'backup_codes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_enabled' => 'boolean',
        'backup_codes' => 'array',
    ];

    /**
     * Define la relación con el modelo User (la configuración de 2FA pertenece a un usuario).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}