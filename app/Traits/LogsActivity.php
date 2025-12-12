<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    protected static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            self::logActivity($model, 'created');
        });

        static::updated(function ($model) {
            self::logActivity($model, 'updated');
        });

        static::deleted(function ($model) {
            self::logActivity($model, 'deleted');
        });
    }

    protected static function logActivity($model, string $event): void
    {
        $properties = [
            'attributes' => $model->getAttributes(),
        ];

        if ($event === 'updated') {
            $properties['old'] = $model->getOriginal();
        }

        ActivityLog::create([
            'log_name' => class_basename($model),
            'description' => "{$event} " . class_basename($model),
            'subject_type' => get_class($model),
            'subject_id' => $model->id,
            'causer_type' => Auth::check() ? get_class(Auth::user()) : null,
            'causer_id' => Auth::id(),
            'properties' => $properties,
            'event' => $event,
        ]);
    }
}
