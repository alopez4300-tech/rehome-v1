<?php

namespace App\Services;

use App\Contracts\CostMeter;

final class DatabaseCostMeter implements CostMeter
{
    public function record(string $runId, int $cents, array $meta = []): void
    {
        // TODO: Implement actual cost tracking
        // Log to database, send to analytics, etc.
        \Log::info('Cost recorded', [
            'run_id' => $runId,
            'cents' => $cents,
            'meta' => $meta,
        ]);
    }
}
