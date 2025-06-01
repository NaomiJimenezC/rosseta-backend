<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LikeNotification extends Notification
{
    use Queueable;

    protected $liker;
    protected $post;

    /**
     * Create a new notification instance.
     *
     * @param  \App\Models\User  $liker
     * @param  \App\Models\Post  $post
     */
    public function __construct($liker, $post)
    {
        $this->liker = $liker;
        $this->post  = $post;
    }

    /**
     * Get the notification’s delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Representación en la base de datos (canal “database”).
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'type'         => 'post_liked',
            'message'      => "@{$this->liker->username} le ha dado me gusta a tu publicación.",
            'liker_id'     => $this->liker->id,
            'liker_name'   => $this->liker->username,
            'post_id'      => $this->post->id,
            'post_excerpt' => substr($this->post->content, 0, 50),
            'url'          => "/posts/{$this->post->id}",
        ];
    }
}
