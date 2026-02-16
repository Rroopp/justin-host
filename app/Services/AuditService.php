<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Log an action to the audit_logs table.
     *
     * @param mixed $user The user performing the action (or null for system)
     * @param string $action Short action name (e.g., 'create', 'update', 'delete')
     * @param string $module Module name (e.g., 'orders', 'inventory')
     * @param int|string|null $targetId The ID of the primary target object
     * @param string $description Human-readable description
     * @param string|null $targetType Class name of the target model (optional)
     * @param array|null $oldValues Old values for updates (optional)
     * @param array|null $newValues New values for updates (optional)
     * @return AuditLog
     */
    public function log($user, string $action, string $module, $targetId, string $description, ?string $targetType = null, ?array $oldValues = null, ?array $newValues = null)
    {
        return AuditLog::create([
            'user_id' => $user ? $user->id : null,
            'user_name' => $user ? ($user->username ?? $user->name) : 'System',
            'action' => $action,
            'module' => $module,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
