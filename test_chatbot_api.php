<?php

$contextPayload = [
    'user_profile' => [
        'name' => 'Ahmed The Patient',
        'medical_condition' => 'Lower Body Paralysis',
        'age' => 28,
        'weight' => 70,
        'gender' => 'male',
    ],
    'relations' => [
        'doctor' => ['name' => 'Dr. Smith', 'phone' => '+20111111111'],
        'companions' => [['name' => 'Mona', 'phone' => '+20122222222']],
    ],
    'wheelchair_status' => [
        'serial_number' => 'CHAIR-TEST-001',
        'battery' => 85,
        'connection' => 'online',
    ],
    'current_health_state' => [
        'heart_rate' => 110,
        'temperature' => 37.5,
        'mpu_monitoring' => [
            'angle' => 45,
            'fall_detected' => true,
            'fainting_risk' => 'critical'
        ]
    ],
    'current_trip' => [
        'is_active' => true,
        'start_location' => 'Unknown',
        'destination' => 'Hospital',
        'current_coordinates' => ['x' => 10.5, 'y' => 20.2],
    ],
    'latest_alerts' => [
        'health' => ['message' => 'High Heart Rate Detected', 'severity' => 'critical', 'timestamp' => '2026-05-28T10:00:00Z'],
        'obstacle' => null,
        'sos' => null,
        'battery' => null,
    ],
];

$messages = [
    // App Usage (Arabic)
    "ازاي اضيف مرافق جديد في التطبيق؟",
    "فين اقدر اشوف نسبة شحن الكرسي؟",
    "ازاى اقدر ابعت رسالة للدكتور من خلال التطبيق؟",
    // App Usage (English)
    "How do I add a new companion to the app?",
    "Where can I find the wheelchair battery percentage?",
    
    // Health / Patient Conditions (Arabic)
    "انا حاسس بتعب شديد ومش قادر اتنفس كويس",
    "ظهري بيوجعني جدا من القعدة على الكرسي",
    "ضربات قلبي سريعة اوي وحاسس بدوخة، اعمل ايه؟",
    // Health / Patient Conditions (English)
    "I feel very tired and my back hurts from sitting.",
    "My heart is beating too fast and I feel dizzy.",
    
    // Wheelchair & Emergencies (Arabic)
    "الكرسي علق ومش عارف اتحرك، في حاجة سادة الطريق.",
    "ابعت رسالة استغاثة للدكتور بتاعي بسرعة!",
    // Wheelchair & Emergencies (English)
    "The wheelchair is stuck, I can't move.",
    "Send an SOS to my doctor right now!"
];

$markdown = "# Chatbot API Test Results & Evaluation\n\n";

foreach ($messages as $msg) {
    $systemMessage = "You are a friendly Chatbot. Context data: " . json_encode($contextPayload);
    
    $ch = curl_init('https://chairpal-ai.duckdns.org/chat');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'message' => ['text' => $msg],
        'system_message' => $systemMessage,
        'max_tokens' => 512,
        'temperature' => 0.7,
        'top_p' => 0.95,
        'user_profile' => $contextPayload['user_profile'],
        'relations' => $contextPayload['relations'],
        'wheelchair_status' => $contextPayload['wheelchair_status'],
        'current_health_state' => $contextPayload['current_health_state'],
        'current_trip' => $contextPayload['current_trip'],
        'latest_alerts' => $contextPayload['latest_alerts'],
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $responseRaw = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    $parsed = json_decode($responseRaw, true);
    $responseText = $parsed['response'] ?? "Error / No Response";
    $intent = $parsed['intent'] ?? "N/A";
    
    $markdown .= "## Request\n**Message:** {$msg}\n\n";
    $markdown .= "## Response\n**Bot Reply:** {$responseText}\n";
    $markdown .= "**Detected Intent:** {$intent}\n\n";
    $markdown .= "---\n\n";
}

file_put_contents('chatbot_evaluation.md', $markdown);
echo "Done testing. Results saved to chatbot_evaluation.md\n";
