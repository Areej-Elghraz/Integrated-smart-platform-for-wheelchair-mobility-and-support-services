<?php

namespace App\Listeners;

use App\Events\SosTriggeredEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendSosChatMessage
{
    protected \App\Services\ChatService $chatService;

    /**
     * Create the event listener.
     */
    public function __construct(\App\Services\ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * Handle the event.
     */
    public function handle(SosTriggeredEvent $event): void
    {
        $messageContent = $event->payload['message'] . "\n\n";
        if (!empty($event->payload['location_link'])) {
            $messageContent .= "Location: " . $event->payload['location_link'];
        }

        foreach ($event->friends as $friend) {
            $this->chatService->sendMessage(
                $event->user,
                $friend->id,
                ['content' => $messageContent],
                null
            );
            \Illuminate\Support\Facades\Log::info("SendSosChatMessage: SOS chat message sent to {$friend->name} ({$friend->id})");
        }
    }
}
