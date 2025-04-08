<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Follow extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'follower_id',
        'followee_id',
    ];

    /**
     * Define la relación con el usuario que sigue (follower).
     */
    public function follower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    /**
     * Define la relación con el usuario que es seguido (followee).
     */
    public function followee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'followee_id');
    }
}