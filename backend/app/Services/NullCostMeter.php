<?php

namespace App\Services;

use App\Contracts\CostMeter;

final class NullCostMeter implements CostMeter
{
    public function record(string $runId, int $cents, array $meta = []): void
    {
        // Null implementation - no overhead when cost tracking is disabled
    }
}