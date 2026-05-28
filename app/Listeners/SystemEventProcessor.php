<?php

namespace App\Listeners;

use App\Core\UnifiedSystemEvent;
use App\Engines\SystemPolicyEngine;
use App\Services\HealthOrchestratorService;
use App\Services\EmergencyOrchestratorService;
use App\Models\EmergencyEvent;
use App\Models\DecisionTraceLog;
use Illuminate\Support\Facades\Log;

class SystemEventProcessor
{
    protected $healthService;
    protected $emergencyService;

    public function __construct(HealthOrchestratorService $healthService, EmergencyOrchestratorService $emergencyService)
    {
        $this->healthService = $healthService;
        $this->emergencyService = $emergencyService;
    }

    public function handle(UnifiedSystemEvent $event)
    {
        $startTime = microtime(true);

        Log::info("SystemEventProcessor: Processing event '{$event->type}' (Severity: {$event->severity})");

        // 1. Get Decision from Policy Engine (Stateless)
        $policyOutput = SystemPolicyEngine::decide($event);
        $decisions = $policyOutput['decisions'];
        $reasoning = $policyOutput['reasoning'];

        // 2. Execute side effects based on decisions
        foreach ($decisions as $decision) {
            $this->executeDecision($decision, $event);
        }

        // 3. Observability: Record Decision Trace
        $latency = (microtime(true) - $startTime) * 1000; // in ms
        
        DecisionTraceLog::create([
            'event_type' => $event->type,
            'event_id' => $event->payload['id'] ?? 0,
            'decisions' => $decisions,
            'reasoning' => $reasoning,
            'latency_ms' => $latency,
            'timestamp_ms' => $event->timestamp_ms,
        ]);

        Log::info("SystemEventProcessor: Completed in {$latency}ms with " . count($decisions) . " decisions.");
    }

    protected function executeDecision(string $decision, UnifiedSystemEvent $event)
    {
        switch ($decision) {
            case 'escalate_to_emergency':
                $this->escalateToEmergency($event);
                break;
            case 'pause_trip':
                // Handled in EmergencyOrchestrator
                break;
            case 'notify_assistant':
                // Send notification
                break;
            case 'log_incident':
                // Log incident report
                break;
        }
    }

    protected function escalateToEmergency(UnifiedSystemEvent $event)
    {
        $emergency = EmergencyEvent::create([
            'e_chair_id' => $event->payload['e_chair_id'],
            'trip_id' => $event->payload['trip_id'] ?? null,
            'event_type' => "health_escalation_" . ($event->payload['prediction_type'] ?? 'unknown'),
            'source_classification' => in_array($event->type, ['heart', 'temperature', 'mpu_monitoring']) ? 'health' : $event->type,
            'severity' => 'critical',
            'timestamp_ms' => $event->timestamp_ms,
        ]);

        $this->emergencyService->handleEmergency($emergency);
    }
}
