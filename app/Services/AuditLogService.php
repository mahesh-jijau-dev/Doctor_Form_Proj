<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;

class AuditLogService
{
    /**
     * Record an audit event. Failures are swallowed so they never disrupt the main application flow.
     */
    public function log(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $description = null,
        array $metadata = []
    ): void {
        try {
            AuditLog::log($action, $entityType, $entityId, $description, $metadata);
        } catch (\Exception $e) {
            Log::warning('AuditLog failed: ' . $e->getMessage());
        }
    }
}
