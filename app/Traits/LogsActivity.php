<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Arr;
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
        // Evita registrar "updates" que no cambiaron nada real (p. ej. solo touch de timestamps).
        if ($event === 'updated' && empty($model->getChanges())) {
            return;
        }

        // Campos a excluir del log (el modelo los define en $activityLogExclude).
        // Sirve para no volcar blobs enormes (input/output/prompt_messages) en cada registro.
        $exclude = property_exists($model, 'activityLogExclude') ? $model->activityLogExclude : [];

        $properties = [
            'attributes' => Arr::except($model->getAttributes(), $exclude),
        ];

        if ($event === 'updated') {
            $properties['old'] = Arr::except($model->getOriginal(), $exclude);
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
