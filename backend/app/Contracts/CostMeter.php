<?php

namespace App\Contracts;

interface CostMeter
{
    public function record(string $runId, int $cents, array $meta = []): void;
}