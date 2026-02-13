<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class IngestionMetricsService
{
    private const KEY_PREFIX = 'northcloud_ingestion_';

    private const KEY_RECEIVED = self::KEY_PREFIX.'received_total';

    private const KEY_SKIPPED = self::KEY_PREFIX.'skipped_non_core_total';

    private const KEY_INGESTED = self::KEY_PREFIX.'ingested_total';

    public function incrementReceived(): void
    {
        Cache::increment(self::KEY_RECEIVED);
    }

    public function incrementSkipped(): void
    {
        Cache::increment(self::KEY_SKIPPED);
    }

    public function incrementIngested(): void
    {
        Cache::increment(self::KEY_INGESTED);
    }

    /**
     * @return array{received_total: int, skipped_non_core_total: int, ingested_total: int, core_ratio: float}
     */
    public function getStats(): array
    {
        $received = (int) Cache::get(self::KEY_RECEIVED, 0);
        $skipped = (int) Cache::get(self::KEY_SKIPPED, 0);
        $ingested = (int) Cache::get(self::KEY_INGESTED, 0);
        $coreRatio = $received > 0 ? round($ingested / $received, 4) : 0.0;

        return [
            'received_total' => $received,
            'skipped_non_core_total' => $skipped,
            'ingested_total' => $ingested,
            'core_ratio' => $coreRatio,
        ];
    }
}
