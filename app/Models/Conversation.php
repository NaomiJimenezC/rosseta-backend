<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = ['last_message_at'];

    public function users()
    {
        return $this->belongsToMany(User::class)
        ->withPivot('joined_at')
        ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}