<?php

namespace App\Engines;

use App\Core\UnifiedSystemEvent;

class SystemPolicyEngine
{
    /**
     * Stateless decision engine.
     * Maps a system event to a set of recommended actions and reasoning.
     */
    public static function decide(UnifiedSystemEvent $event): array
    {
        $actions = [];
        $reasoning = [];

        // 1. Determine base actions based on severity
        if ($event->severity === 'critical') {
            $actions[] = 'notify_assistant';
            $actions[] = 'log_incident';
            $reasoning[] = "Severity is critical, triggering immediate notification and incident logging.";
            
            if (in_array($event->type, ['heart', 'temperature', 'mpu_monitoring', 'emergency', 'obstacle'])) {
                $actions[] = 'pause_trip';
                $reasoning[] = "Event type '{$event->type}' is safety-critical, requesting trip pause.";
            }
        }

        // 2. Specific escalation rules
        if (in_array($event->type, ['heart', 'temperature', 'mpu_monitoring']) && $event->severity === 'critical') {
            $actions[] = 'escalate_to_emergency';
            $reasoning[] = "Critical health event detected, escalating to emergency subsystem.";
        }

        return [
            'decisions' => array_unique($actions),
            'reasoning' => implode(' | ', $reasoning)
        ];
    }
}
