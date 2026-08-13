<?php

namespace App\Models\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            static::recordActivity('created', $model, $model->getAttributes());
        });

        static::updated(function ($model) {
            static::recordActivity('updated', $model, $model->getChanges());
        });

        static::deleted(function ($model) {
            static::recordActivity('deleted', $model, $model->getAttributes());
        });
    }

    protected static function recordActivity(string $action, $model, array $changes): void
    {
        $userId = Auth::id() ?? ($model->user_id ?? null);

        ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'model_type' => class_basename($model),
            'model_id' => $model->id ?? null,
            'description' => class_basename($model) . ' ' . $action . ' (ID: ' . ($model->id ?? 'N/A') . ')',
            'changes_json' => $changes,
            'ip_address' => Request::ip() ?? '127.0.0.1',
            'created_at' => now(),
        ]);
    }
}
