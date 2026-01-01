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
                Log::warning('Skipping article with quality score below threshold', [
                    'quality_score' => $data['quality_score'],
                    'min_quality_score' => $minQualityScore,
                ]);

                return;
            }

            ProcessIncomingArticle::dispatch($data);

            $title = $data['title'] ?? $data['og_title'] ?? 'Untitled';
            $this->info("✓ Dispatched article: {$title}");
        } catch (\Exception $e) {
            Log::error('Failed to process Redis message', [
                'error' => $e->getMessage(),
                'message_preview' => substr($message, 0, 200),
            ]);
        }
    }

    protected function isValidMessage(array $data): bool
    {
        // Must have id and publisher
        if (! isset($data['id']) || ! isset($data['publisher']) || ! is_array($data['publisher'])) {
            return false;
        }

        // Must have either title or og_title
        if (! isset($data['title']) && ! isset($data['og_title'])) {
            return false;
        }

        return true;
    }
}
