<?php

namespace App\Traits;

trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            $model->logActivity('created', $model->getAttributes());
        });

        static::updated(function ($model) {
            $changes = $model->getChanges();
            unset($changes['updated_at']);

            if (!empty($changes)) {
                $diff = [];
                foreach ($changes as $key => $newValue) {
                    $diff[$key] = [
                        'old' => $model->getOriginal($key),
                        'new' => $newValue,
                    ];
                }
                $model->logActivity('updated', $diff);
            }
        });

        static::deleted(function ($model) {
            $model->logActivity('deleted', $model->getAttributes());
        });
    }

    protected function logActivity(string $action, array $values): void
    {
        $excluded = property_exists($this, 'auditExclude')
            ? $this->auditExclude
            : ['password', 'remember_token', 'two_factor_secret'];

        foreach ($excluded as $field) {
            if (isset($values[$field])) {
                $values[$field] = '[REDACTED]';
            }
        }

        \App\Models\ActivityLog::create([
            'user_id'    => auth()->id(),
            'action'     => $action,
            'module'     => class_basename(static::class),
            'table_name' => $this->getTable(),
            'record_id'  => $this->getKey(),
            'old_values' => $action === 'updated' ? json_encode($values) : null,
            'new_values' => json_encode($values),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
