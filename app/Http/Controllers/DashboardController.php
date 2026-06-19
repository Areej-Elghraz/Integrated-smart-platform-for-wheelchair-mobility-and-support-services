<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Trip;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DashboardController extends ApiController
{
    public static function getDashboardData($targetUser, $requestingUser = null, $filter = 'today', $limit = null, $search = null)
    {
        $wheelchairs = $targetUser->wheelchairs()->get();
        $wheelchairIds = $wheelchairs->pluck('id');

        // 1. Current Vital Data & AI Recommendation
        $aiData = null;
        $currentVitals = null;

        // Try Redis first for real-time data (before 5-minute MySQL sync)
        foreach ($wheelchairIds as $wId) {
            $cachedVital = null;
            try {
                $cachedVital = \Illuminate\Support\Facades\Redis::get("latest_vital_state_{$wId}");
            } catch (\Exception $e) {
                // Redis not available, skip
            }

            if ($cachedVital) {
                $parsed = json_decode($cachedVital);
                if ($parsed) {
                    $currentVitals = [
                        'heart' => [
                            'value' => $parsed->heart_rate ?? null,
                            'status' => $parsed->heart_rate_status ?? null
                        ],
                        'temperature' => [
                            'value' => $parsed->temperature ?? null,
                            'status' => $parsed->temperature_status ?? null
                        ],
                        'obstacle' => [
                            'movement' => 'movement',
                            'mpu_angle' => $parsed->mpu_angle ?? null,
                            'status' => $parsed->fall_status ?? null
                        ]
                    ];
                    // Build aiData-like object for recommendation section
                    $aiData = $parsed;
                    break;
                }
            }
        }

        // Fallback to MySQL if Redis had no data
        if (!$currentVitals) {
            $aiData = \App\Models\AiRecommendation::whereIn('wheelchair_id', $wheelchairIds)->latest()->first();

            if ($aiData) {
                $currentVitals = [
                    'heart' => [
                        'value' => $aiData->heart_rate,
                        'status' => $aiData->heart_rate_status
                    ],
                    'temperature' => [
                        'value' => $aiData->temperature,
                        'status' => $aiData->temperature_status
                    ],
                    'obstacle' => [
                        'movement' => 'movement',
                        'mpu_angle' => $aiData->mpu_angle,
                        'status' => $aiData->fall_status
                    ]
                ];
            }
        }

        // 2. Overviews (Trends)
        $dateFilter = now();
        if ($filter === 'today') $dateFilter = now()->startOfDay();
        elseif ($filter === 'last_week') $dateFilter = now()->subWeek();
        elseif ($filter === 'last_month') $dateFilter = now()->subMonth();
        elseif ($filter === 'last_year') $dateFilter = now()->subYear();

        $aggregatedData = \Illuminate\Support\Facades\DB::table('sensor_readings_aggregated')
            ->whereIn('wheelchair_id', $wheelchairIds)
            ->where('window_start', '>=', $dateFilter)
            ->orderBy('window_start', 'asc')
            ->get();

        $overviews = [
            'health_rate' => [],
            'temperature' => [],
            'movement' => [],
        ];

        foreach ($aggregatedData as $row) {
            $timeLabel = \Carbon\Carbon::parse($row->window_start)->format($filter === 'today' ? 'H:i' : 'Y-m-d');

            if ($row->heart_rate_avg) {
                $overviews['health_rate'][] = ['x_axis' => $timeLabel, 'y_axis' => round($row->heart_rate_avg, 1)];
            }
            if ($row->temperature_avg) {
                $overviews['temperature'][] = ['x_axis' => $timeLabel, 'y_axis' => round($row->temperature_avg, 1)];
            }
            if ($row->mpu_angle_avg) {
                $overviews['movement'][] = ['x_axis' => $timeLabel, 'y_axis' => round($row->mpu_angle_avg, 1)];
            }
        }

        // 3. Recent Alerts (Latest events regardless of type)
        $recentAlerts = Event::whereIn('wheelchair_id', $wheelchairIds)
            ->latest()
            ->take(10)
            ->get();

        // 4. Last Visited Places (Moved to PlaceController@lastVisited)
        // 4. Last Visited Places (Only for Patient & Companion)
        $lastVisitedPlaces = [];
        if (!$requestingUser || $requestingUser->role !== 'doctor') {
            $query = Trip::whereHas('wheelchair', function ($q) use ($targetUser) {
                $q->where('user_id', $targetUser->id);
            })->where('status', 'completed')->with('place')->latest();

            if ($search) {
                $query->whereHas('place', function ($q) use ($search) {
                    $q->search($search);
                });
            }
            if ($limit) {
                $query->take($limit);
            }
            $lastVisitedPlaces = $query->get()->pluck('place')->filter()->values();
        }

        // 5. Last Trip Updates (Only for Patient & Companion)
        $lastTripUpdates = null;
        if (!$requestingUser || $requestingUser->role !== 'doctor') {
            $activeTrip = Trip::with(['place', 'wheelchair'])
                ->whereHas('wheelchair', function ($q) use ($targetUser) {
                    $q->where('user_id', $targetUser->id);
                })->whereIn('status', ['started', 'in_progress'])->latest()->first();

            if ($activeTrip) {
                $lastTripUpdates = $activeTrip;
            }
        }

        return [
            'current_vitals' => $currentVitals,
            'overviews' => $overviews,
            'recent_alerts' => $recentAlerts,
            'ai_recommendation' => $aiData ? [
                'risk_level' => $aiData->risk_level,
                'recommendation' => $aiData->recommendation
            ] : null,
            'last_visited_places' => $lastVisitedPlaces,
            'last_trip_updates' => $lastTripUpdates,
        ];
    }

    public function userDashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $targetUserId = $request->query('user_id');

        // Check if accessed by a companion or doctor
        if ($targetUserId && $targetUserId != $user->id) {
            if (!in_array($user->role, ['companion', 'doctor'])) {
                return $this->errorResponse('Unauthorized to view other users.', 403);
            }

            // Verify connection
            $isFriend = $user->friends()->wherePivot('status', 'accepted')->where('users.id', $targetUserId)->exists();
            if (!$isFriend) {
                return $this->errorResponse('Not connected to this user.', 403);
            }
            $targetUser = User::findOrFail($targetUserId);
        } else {
            if ($user->role !== 'user') {
                return $this->errorResponse('Unauthorized. Patients only, unless user_id is provided.', 403);
            }
            $targetUser = $user;
        }

        $filter = request()->query('filter', 'today');
        if (!in_array($filter, ['today', 'last_week', 'last_month', 'last_year'])) {
            return $this->errorResponse('Invalid filter. Allowed values: today, last_week, last_month, last_year', 400);
        }

        $limitStr = request()->query('limit');
        $limit = $limitStr ? (int) $limitStr : null;
        if ($limit !== null && $limit <= 0) {
            return $this->errorResponse('Limit must be a positive integer', 400);
        }

        $search = request()->query('search');

        $data = self::getDashboardData($targetUser, $user, $filter, $limit, $search);

        return $this->successResponse('User dashboard data retrieved.', parameters: ['data' => $data]);
    }

    public function companionDashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'companion') {
            return $this->errorResponse('Unauthorized access.', 403);
        }

        $patient = $user->connectedUserForCompanion;
        if (!$patient) {
            return $this->successResponse('Companion dashboard data retrieved.', parameters: ['data' => null]);
        }

        $filter = request()->query('filter', 'today');
        $limitStr = request()->query('limit');
        $limit = $limitStr ? (int) $limitStr : null;
        $search = request()->query('search');

        $data = self::getDashboardData($patient, $user, $filter, $limit, $search);

        return $this->successResponse('Companion dashboard data retrieved.', parameters: ['data' => $data]);
    }

    public function doctorDashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role !== 'doctor') {
            return $this->errorResponse('Unauthorized access.', 403);
        }

        $patients = $user->connectedPatients;

        $stats = [
            'total' => $patients->count(),
            'normal' => 0,
            'medium' => 0,
            'critical' => 0,
        ];

        foreach ($patients as $patient) {
            $wheelchairIds = $patient->wheelchairs()->pluck('id');
            $aiData = \App\Models\AiRecommendation::whereIn('wheelchair_id', $wheelchairIds)->latest()->first();
            if ($aiData) {
                $status = $aiData->risk_level === 'low' ? 'normal' : $aiData->risk_level;
                $stats[$status] = ($stats[$status] ?? 0) + 1;
            } else {
                $stats['normal']++;
            }
        }

        return $this->successResponse('Doctor dashboard data retrieved.', parameters: [
            'data' => [
                'statistics' => $stats,
                'patients' => $patients,
            ]
        ]);
    }

    public function orgAdminDashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->isOrganization() && !$user->isOrganizationAdmin()) {
            return $this->errorResponse('Unauthorized access. Only Organization Admins can view this dashboard.', 403);
        }

        $organizations = $user->organizations()->get();
        $orgIds = $organizations->pluck('id');

        $recentBuildings = \App\Models\Building::whereIn('organization_id', $orgIds)
            ->latest('updated_at')
            ->take(25)
            ->get()
            ->map(function ($b) {
                return [
                    'type' => 'building_updated',
                    'message' => 'Building updated: ' . $b->name,
                    'created_at' => $b->updated_at,
                ];
            });

        $recentPlaces = \App\Models\Place::whereHas('floor.building', function ($q) use ($orgIds) {
            $q->whereIn('organization_id', $orgIds);
        })
            ->latest('updated_at')
            ->take(25)
            ->get()
            ->map(function ($p) {
                return [
                    'type' => 'place_updated',
                    'message' => 'Place updated: ' . $p->name,
                    'created_at' => $p->updated_at,
                ];
            });

        $auditLogs = $recentBuildings->concat($recentPlaces)->sortByDesc('created_at')->take(50)->values();

        return $this->successResponse('Organization Admin dashboard data retrieved.', parameters: [
            'data' => [
                'organizations' => $organizations,
                'recent_activity_logs' => $auditLogs,
            ]
        ]);
    }
}