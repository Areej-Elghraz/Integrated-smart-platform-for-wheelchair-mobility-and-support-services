<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait LogsAdminActions
{
    protected function logAdminAction(string $action, $model = null, array $details = [])
    {
        $user = Auth::guard('sanctum')->user();
        if ($user && $user->isOrganizationAdmin()) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => $action,
                'model_type' => $model ? class_basename($model) : null,
                'model_id' => $model ? $model->id : null,
                'details' => json_encode($details),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
    }
}
