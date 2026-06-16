<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MapUploaded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $map;

    /**
     * Create a new event instance.
     */
    public function __construct(\App\Models\Map $map)
    {
        $this->map = $map;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('floor.' . $this->map->floor_id),
        ];
    }

    /**
     * Get the data to broadcast.
     * Prevents large yaml_data from causing Pusher 'Payload too large' error.
     */
    public function broadcastWith(): array
    {
        return [
            'map_id' => $this->map->id,
            'floor_id' => $this->map->floor_id,
            'map_file_url' => $this->map->map_file,
            'action' => 'map_uploaded'
        ];
    }
}
