<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserFollowed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $follower;
    public User $followee;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\User  $follower  The user who followed.
     * @param  \App\Models\User  $followee  The user who was followed.
     */
    public function __construct(User $follower, User $followee)
    {
        $this->follower = $follower;
        $this->followee = $followee;
    }

    /**
     * The name of the event as seen by the client.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'user.followed';
    }

    /**
     * Data to broadcast to the client.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'follower_id'   => $this->follower->id,
            'follower_name' => $this->follower->name,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|\Illuminate\Broadcasting\Channel[]
     */
    public function broadcastOn()
    {
        // Usamos el canal privado de notificaciones del followee
        return new PrivateChannel('notifications.' . $this->followee->id);
    }

    /**
     * Queue name for broadcasting.
     *
     * @return string|null
     */
    public function broadcastQueue(): ?string
    {
        return 'broadcasts';
    }
}
