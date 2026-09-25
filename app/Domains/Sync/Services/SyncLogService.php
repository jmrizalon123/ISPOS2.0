<?php

namespace App\Domains\Sync\Services;

use App\Models\PosDevice;
use App\Models\SyncLog;

class SyncLogService
{
    /** @param  array<string, mixed>|null  $summary */
    public function record(
        PosDevice $device,
        string $direction,
        string $status,
        int $recordsCount = 0,
        ?array $summary = null,
        ?string $errorMessage = null,
    ): SyncLog {
        return SyncLog::create([
            'pos_device_id' => $device->id,
            'direction' => $direction,
            'status' => $status,
            'records_count' => $recordsCount,
            'summary' => $summary,
            'error_message' => $errorMessage,
            'created_at' => now(),
        ]);
    }
}
