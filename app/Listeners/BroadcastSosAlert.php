<?php

namespace App\Listeners;

use App\Events\SosTriggeredEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class BroadcastSosAlert
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SosTriggeredEvent $event): void
    {
        foreach ($event->friends as $friend) {
            broadcast(new \App\Events\SosTriggered($friend->id, $event->payload));
            \Illuminate\Support\Facades\Log::info("BroadcastSosAlert: SOS broadcast to {$friend->name} ({$friend->id}) for patient {$event->user->name}");
        }
    }
}
