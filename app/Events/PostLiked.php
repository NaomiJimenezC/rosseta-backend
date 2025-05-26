<?php

namespace App\Events;

use App\Models\User;
use App\Models\Post;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostLiked implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $liker;
    public Post $post;

    public function __construct(User $liker, Post $post)
    {
        $this->liker = $liker;
        $this->post  = $post;
    }

    public function broadcastOn()
    {
        // El autor del post recibirá la notificación
        return new PrivateChannel('notifications.' . $this->post->user_id);
    }

    public function broadcastAs(): string
    {
        return 'post.liked';
    }

    public function broadcastWith(): array
    {
        return [
            'post_id'    => $this->post->id,
            'liker_id'   => $this->liker->id,
            'liker_name' => $this->liker->name,
        ];
    }
}
