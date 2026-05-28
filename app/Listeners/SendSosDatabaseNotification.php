<?php

namespace App\Listeners;

use App\Events\SosTriggeredEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendSosDatabaseNotification
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
            $friend->notify(new \App\Notifications\DatabaseNotification\SosAlertNotification($event->user, $event->payload));
            \Illuminate\Support\Facades\Log::info("SendSosDatabaseNotification: SOS DB Notification saved for {$friend->name} ({$friend->id})");
        }
    }
}
