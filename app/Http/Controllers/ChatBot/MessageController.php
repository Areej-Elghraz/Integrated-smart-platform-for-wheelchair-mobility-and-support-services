<?php

namespace App\Http\Controllers\ChatBot;

use App\Http\Controllers\ApiController;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class MessageController extends ApiController
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $cacheKey = "chat_sessions_user_{$userId}";
        $sessions = Cache::remember($cacheKey, 3600, function () use ($request) {
            return $request->user()->chatSessions()->latest()->get();
        });
        return response()->json(['data' => $sessions]);
    }

    public function storeSession(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
        ]);

        $session = $request->user()->chatSessions()->create([
            'title' => $request->input('title', 'New Chat'),
        ]);

        Cache::forget("chat_sessions_user_{$request->user()->id}");

        return response()->json(['message' => __('messages.chat_session_created'), 'data' => $session], 201);
    }

    public function showSession(Request $request, ChatSession $session)
    {
        if ($session->user_id !== $request->user()->id) {
            return response()->json(['error' => __('messages.unauthorized')], 403);
        }

        $session->load('messages');
        return response()->json(['data' => $session]);
    }

    public function destroySession(Request $request, ChatSession $session)
    {
        if ($session->user_id !== $request->user()->id) {
            return response()->json(['error' => __('messages.unauthorized')], 403);
        }

        $session->delete();
        Cache::forget("chat_sessions_user_{$request->user()->id}");
        return response()->json(['message' => __('messages.chat_session_deleted')]);
    }

    public function chat(Request $request, ChatSession $session)
    {
        if ($session->user_id !== $request->user()->id) {
            return response()->json(['error' => __('messages.unauthorized')], 403);
        }

        $request->validate([
            'message' => 'nullable|string',
            'media' => 'nullable|array',
            'media.*' => 'file|mimes:jpeg,png,jpg,gif,mp3,wav|max:10240',
        ]);

        $content = $request->input('message');
        $mediaFiles = $request->file('media');

        if (!$content && empty($mediaFiles)) {
            return response()->json(['error' => __('messages.message_media_required')], 400);
        }

        $attachments = [];

        if (!empty($mediaFiles)) {
            foreach ($mediaFiles as $mediaFile) {
                $mediaPath = $mediaFile->store('chat_media', 'public');
                $mimeType = $mediaFile->getClientMimeType();
                $type = 'file';

                if (str_starts_with($mimeType, 'image/')) {
                    $type = 'image';
                } elseif (str_starts_with($mimeType, 'audio/')) {
                    $type = 'audio';
                }

                $attachments[] = [
                    'path' => $mediaPath,
                    'type' => $type,
                    'mime_type' => $mimeType,
                ];
            }
        }
        // dd($content);

        // 1. Save user's message
        $userMessage = $session->messages()->create([
            'sender_type' => 'user',
            'content' => $content,
            'attachments' => !empty($attachments) ? $attachments : null,
        ]);

        // 2. Build the System Context Payload
        $user = $request->user();
        $wheelchair = $user->wheelchairs()->first();

        $friends = $user->friends; // Uses the attribute we defined
        $doctor = $friends->where('role', 'doctor')->first();
        $companions = $friends->where('role', 'companion')->map(fn($c) => ['name' => $c->name, 'phone' => $c->phone])->values();

        $medicalConditions = $user->medicalConditions->pluck('name')->implode(' / ');

        $currentTrip = null;
        $vitalState = null;
        $latestAlerts = [];

        if ($wheelchair) {
            $trip = $wheelchair->trips()->where('status', 'in_progress')->first();
            if ($trip) {
                $currentTrip = [
                    'is_active' => true,
                    'start_location' => 'Unknown',
                    'destination' => $trip->destination_place_id ? 'Place ID: ' . $trip->destination_place_id : 'Unknown',
                    'current_coordinates' => ['x' => $wheelchair->x_coordinate, 'y' => $wheelchair->y_coordinate],
                ];
            }
            $vitalState = $wheelchair->aiRecommendations()->latest()->first();

            // Get latest alert for EACH type
            $alertTypes = ['heart', 'temperature', 'mpu_monitoring', 'obstacle', 'sos', 'battery'];
            $latestAlerts = [];
            foreach ($alertTypes as $type) {
                $event = $wheelchair->events()->where('type', $type)->latest()->first();
                $latestAlerts[$type] = $event ? [
                    'message' => $event->message,
                    'severity' => $event->severity,
                    'timestamp' => $event->created_at->toIso8601String(),
                ] : null;
            }
        }

        $contextPayload = [
            'user_profile' => [
                'name' => $user->name,
                'medical_condition' => $medicalConditions ?: 'Unknown',
                'age' => $user->age,
                'weight' => $user->weight,
                'gender' => $user->gender,
            ],
            'relations' => [
                'doctor' => $doctor ? ['name' => $doctor->name, 'phone' => $doctor->phone] : null,
                'companions' => $companions,
            ],
            'wheelchair_status' => $wheelchair ? [
                'serial_number' => $wheelchair->serial_number,
                'battery' => $wheelchair->battery,
                'connection' => $wheelchair->connection_state,
            ] : null,
            'current_health_state' => $vitalState ? [
                'heart_rate' => $vitalState->heart_rate,
                'temperature' => $vitalState->temperature,
                'mpu_monitoring' => [
                    'angle' => $vitalState->mpu_angle,
                    'fall_detected' => $vitalState->fall_status === 'critical',
                    "fainting_risk" => $vitalState->fall_status,
                ]
            ] : null,
            'current_trip' => $currentTrip,
            'latest_alerts' => $latestAlerts,
        ];

        // 3. Call the HF space bot API
        $textToSend = $content ?? 'User sent attachment(s).';

        // Append context as a hidden JSON string for the AI
        $systemMessage = $request->input('system_message', "You are a friendly Chatbot. Context data: ") . json_encode($contextPayload);

        $maxTokens = $request->input('max_tokens', 512);
        $temperature = $request->input('temperature', 0.7);
        $topP = $request->input('top_p', 0.95);

        try {
            $postResponse = Http::timeout(30)->post('https://chairpal-ai.duckdns.org/chat', [
                'message' => [
                    'text' => $textToSend
                ],
                'system_message' => (string) $systemMessage,
                'max_tokens' => (int) $maxTokens,
                'temperature' => (float) $temperature,
                'top_p' => (float) $topP,

                'user_profile' => [
                    'name' => $user->name,
                    'medical_condition' => $medicalConditions ?: 'Unknown',
                    'age' => $user->age,
                    'weight' => $user->weight,
                    'gender' => $user->gender,
                ],
                'relations' => [
                    'doctor' => $doctor ? ['name' => $doctor->name, 'phone' => $doctor->phone] : null,
                    'companions' => $companions,
                ],
                'wheelchair_status' => $wheelchair ? [
                    'serial_number' => $wheelchair->serial_number,
                    'battery' => $wheelchair->battery,
                    'connection' => $wheelchair->connection_state,
                ] : null,
                'current_health_state' => $vitalState ? [
                    'heart_rate' => $vitalState->heart_rate,
                    'temperature' => $vitalState->temperature,
                    'mpu_monitoring' => [
                        'angle' => $vitalState->mpu_angle,
                        'fall_detected' => $vitalState->fall_status === 'critical',
                        "fainting_risk" => $vitalState->fall_status,
                    ]
                ] : null,
                'current_trip' => $currentTrip,
                'latest_alerts' => $latestAlerts,
            ]);

            if ($postResponse->failed()) {
                return response()->json(['error' => "Failed to initiate chat (POST). Response: " . $postResponse->body()], 500);
            }

            $responseData = $postResponse->json();
            $botFinalResponse = $responseData['response'] ?? 'Sorry, I did not understand that.';

            // 3. حفظ رد البوت في قاعدة البيانات
            $botMessage = $session->messages()->create([
                'sender_type' => 'bot',
                'content' => $botFinalResponse,
            ]);

            return response()->json([
                'user_message' => $userMessage,
                'bot_message' => $botMessage,
                'intent' => $responseData['intent'] ?? null,
                'confidence' => $responseData['confidence'] ?? null,
                'language' => $responseData['language'] ?? null,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => __('messages.bot_service_unavailable') . " " . $e->getMessage()], 500);
        }
        // try {
        //     $response = Http::post('https://sarasalem-me-app.hf.space/api/predict', [
        //         'data' => [
        //             $textToSend,
        //             $systemMessage,
        //             $maxTokens,
        //             $temperature,
        //             $topP,
        //         ],
        //         'fn_index' => 0,
        //     ]);

        //     if ($response->failed()) {
        //         // Return user message but state bot failed
        //         throw new \Exception(__('messages.user_message_saved_bot_failed'), 500);
        //     }

        //     $rawResponse = $response->json('data')[0] ?? '';
        //     preg_match('/\[ASS\](.*?)\[\/ASS\]/s', $rawResponse, $matches);
        //     $finalResponse = isset($matches[1]) ? trim($matches[1]) : trim($rawResponse);

        //     // dd($finalResponse);

        //     // 3. Save the bot's response
        //     $botMessage = $session->messages()->create([
        //         'sender_type' => 'bot',
        //         'content' => $finalResponse,
        //     ]);

        //     return response()->json([
        //         'user_message' => $userMessage,
        //         'bot_message' => $botMessage
        //     ]);

        // } catch (\Exception $e) {
        //     throw new \Exception(__('messages.bot_service_unavailable') . $e->getMessage(), 500);
        // }
    }

    public function reactToMessage(Request $request, ChatMessage $message)
    {
        $request->validate([
            'reaction' => 'nullable|in:like,dislike',
        ]);

        // Ensure the message belongs to a session owned by the authenticated user
        if ($message->session->user_id !== $request->user()->id) {
            return response()->json(['error' => __('messages.unauthorized')], 403);
        }

        // Usually users react to bot messages, but let's allow it for any message in their session
        $message->update([
            'reaction' => $request->input('reaction')
        ]);

        return response()->json(['message' => __('messages.reaction_updated'), 'data' => $message]);
    }
}
