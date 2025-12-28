<?php

namespace App\Console\Commands;

use App\Jobs\ProcessIncomingArticle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class SubscribeToArticleFeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'articles:subscribe {channel=articles:crime}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Subscribe to external Redis pub/sub for incoming articles';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $channel = $this->argument('channel');
        $minQualityScore = (int) env('ARTICLES_MIN_QUALITY_SCORE', 0);

        $this->info("Subscribing to Redis channel: {$channel}");

        if ($minQualityScore > 0) {
            $this->info("Filtering articles with quality_score >= {$minQualityScore}");
        }

        Redis::subscribe([$channel], function (string $message) use ($minQualityScore) {
            try {
                $data = json_decode($message, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('Failed to decode Redis message', [
                        'error' => json_last_error_msg(),
                        'message_preview' => substr($message, 0, 200),
                    ]);

                    return;
                }

                // Validate publisher message format
                if (! $this->validatePublisherMessage($data)) {
                    Log::warning('Invalid publisher message format', [
                        'data_keys' => array_keys($data),
                    ]);

                    return;
                }

                // Optional quality score filtering
                if ($minQualityScore > 0) {
                    $qualityScore = $data['quality_score'] ?? 0;
                    if ($qualityScore < $minQualityScore) {
                        if ($this->getOutput()->isVerbose()) {
                            $this->line("Skipping article with quality_score {$qualityScore} < {$minQualityScore}");
                        }

                        return;
                    }
                }

                ProcessIncomingArticle::dispatch($data);

                if ($this->getOutput()->isVerbose()) {
                    $title = $data['title'] ?? 'Unknown';
                    $this->info("Dispatched article: {$title}");
                }
            } catch (\Exception $e) {
                Log::error('Failed to process Redis message', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'message_preview' => substr($message, 0, 200),
                ]);
            }
        });
    }

    /**
     * Validate that the message follows the publisher service format.
     */
    protected function validatePublisherMessage(array $data): bool
    {
        // Check for required fields from publisher format
        $requiredFields = ['id', 'title', 'canonical_url', 'source', 'published_date'];

        foreach ($requiredFields as $field) {
            if (! isset($data[$field])) {
                return false;
            }
        }

        // Validate publisher metadata exists
        if (! isset($data['publisher']) || ! is_array($data['publisher'])) {
            return false;
        }

        return true;
    }
}
