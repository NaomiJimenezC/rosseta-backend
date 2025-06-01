<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FollowNotification extends Notification
{
    use Queueable;

    protected $follower;

    /**
     * @param \App\Models\User $follower  El usuario que ha seguido.
     */
    public function __construct($follower)
    {
        $this->follower = $follower;
    }

    /**
     * Solo canal "database".
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * Lo que se guardará en la columna `data` de `notifications`.
     */
    public function toArray($notifiable): array
    {
        return [
            'type'          => 'follow',
            'message'       => "{$this->follower->username} te ha empezado a seguir.",
            'follower_id'   => $this->follower->id,
            'follower_name' => $this->follower->username,
            'url'           => "/profile/{$this->follower->id}",
        ];
    }
}
