<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogService
{
    public function log(?User $user, string $action, ?string $description = null, ?Request $request = null): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'description' => $description,
            'ip_address' => $request?->ip(),
        ]);
    }
}
