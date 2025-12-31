<?php

namespace App\Console\Commands;

use App\Jobs\ProcessIncomingArticle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class SubscribeToArticleFeed extends Command
{
    protected $signature = 'articles:subscribe {channel=articles:crime}';

    protected $description = 'Subscribe to external Redis pub/sub for incoming articles';

    public function handle(): void
    {
        $channel = $this->argument('channel');
        $minQualityScore = (int) config('database.articles.min_quality_score', 0);

        $this->info("Subscribing to Redis channel: {$channel}");

        if ($minQualityScore > 0) {
            $this->info("Filtering articles with quality_score >= {$minQualityScore}");
        }

        Redis::subscribe([$channel], function (string $message) use ($minQualityScore) {
            $this->processMessage($message, $minQualityScore);
        });
    }

    protected function processMessage(string $message, int $minQualityScore): void
    {
        try {
            $data = json_decode($message, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to decode Redis message', [
                    'error' => json_last_error_msg(),
                    'message_preview' => substr($message, 0, 200),
                ]);

                return;
            }

            if (! $this->isValidMessage($data)) {
                Log::warning('Invalid publisher message format', [
                    'data_keys' => array_keys($data),
                ]);

                return;
            }

            if ($minQualityScore > 0 && ($data['quality_score'] ?? 0) < $minQualityScore) {
                return;
            }

            ProcessIncomingArticle::dispatch($data);

            $this->info("✓ Dispatched article: {$data['title']}");
        } catch (\Exception $e) {
            Log::error('Failed to process Redis message', [
                'error' => $e->getMessage(),
                'message_preview' => substr($message, 0, 200),
            ]);
        }
    }

    protected function isValidMessage(array $data): bool
    {
        $requiredFields = ['id', 'title', 'canonical_url', 'source', 'published_date', 'publisher'];

        foreach ($requiredFields as $field) {
            if (! isset($data[$field])) {
                return false;
            }
        }

        return is_array($data['publisher']);
    }
}
