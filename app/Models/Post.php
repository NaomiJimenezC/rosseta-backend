<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'users_id',
        'content',
        'image_url',
        'caption',
    ];

    /**
     * Define la relación con el modelo User (un post pertenece a un usuario).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}