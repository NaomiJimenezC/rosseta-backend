<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class   Like extends Model
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
    ];

    /**
     * Define la relación con el modelo Post (un "me gusta" pertenece a un post).
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Define la relación con el modelo User (un "me gusta" fue dado por un usuario).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}