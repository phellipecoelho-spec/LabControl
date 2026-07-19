<?php

namespace App\Services;

use App\Models\ActivityLog;

class ActivityLogService
{
    public function log(
        string $action,
        string $module,
        ?string $tableName = null,
        ?string $recordId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): void {
        ActivityLog::create([
            'user_id'    => auth()->id(),
            'action'     => $action,
            'module'     => $module,
            'table_name' => $tableName,
            'record_id'  => $recordId,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function logAuth(string $action, ?string $email = null, ?array $extra = null): void
    {
        $this->log(
            action: $action,
            module: 'auth',
            newValues: $extra ?? ($email ? ['email' => $email] : null),
        );
    }
}
