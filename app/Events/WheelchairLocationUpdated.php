<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WheelchairLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $wheelchairId;
    public $x;
    public $y;
    public $angle;

    public function __construct($wheelchairId, $x, $y, $angle)
    {
        $this->wheelchairId = $wheelchairId;
        $this->x = $x;
        $this->y = $y;
        $this->angle = $angle;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("wheelchair.{$this->wheelchairId}.location"),
        ];
    }
}
