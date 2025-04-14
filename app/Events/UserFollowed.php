<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserFollowed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $follower;
    public $followee;

    /**
     * Create a new event instance.
     *
     * @param  \App\Models\User  $follower The user who followed.
     * @param  \App\Models\User  $followee The user who was followed.
     * @return void
     */
    public function __construct(User $follower, User $followee)
    {
        $this->follower = $follower;
        $this->followee = $followee;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->followee->id),
        ];
    }

    /**
     * The name of the queue to route broadcast messages on.
     *
     * @return string|null
     */
    public function broadcastQueue(): ?string
    {
        return 'broadcasts';
    }
}