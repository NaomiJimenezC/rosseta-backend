<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OauthAuthorization extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'oauth_app_id',
        'user_id',
        'access_token',
        'refresh_token',
        'expires_at',
    ];

    /**
     * Define la relación con la aplicación OAuth.
     */
    public function oauthApp(): BelongsTo
    {
        return $this->belongsTo(OauthApp::class, 'oauth_app_id', 'oauth_app_id');
    }

    /**
     * Define la relación con el usuario.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}