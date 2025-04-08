<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'post_id',
        'user_id',
        'content',
    ];

    /**
     * Define la relación con el modelo Post (un comentario pertenece a un post).
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Define la relación con el modelo User (un comentario fue escrito por un usuario).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}