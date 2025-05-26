<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'phone_number',
        'is_2fa_enabled',
        'birthday',
        'profile_picture_url',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_2fa_enabled' => 'boolean',
        'birthday' => 'date',
    ];

    /**
     * The users who follow this user.
     */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'followee_id', 'follower_id')->withTimestamps();
    }

    /**
     * The users that this user follows.
     */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'followee_id')->withTimestamps();
    }

    /**
     * Verifica si el usuario actual sigue al usuario dado.
     *
     * @param  \App\Models\User  $user  El usuario que se desea verificar.
     * @return bool
     */
    public function isFollowing(User $user): bool
    {
        return $this->following()->where('followee_id', $user->id)->exists();
    }

    /**
     * Get the likes associated with the user.
     */
    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    /**
     * Get the comments associated with the user.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get the posts associated with the user.
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'users_id');
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class)
        ->withPivot('joined_at')
        ->withTimestamps();
        }

    public function messages()
    {
        return $this->hasMany(Message::class, 'sender_id');
        }

}