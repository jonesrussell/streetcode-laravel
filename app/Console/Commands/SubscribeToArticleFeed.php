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
    protected $signature = 'articles:subscribe {channel=articles}';

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

        $this->info("Subscribing to Redis channel: {$channel}");

        Redis::subscribe([$channel], function (string $message) {
            try {
                $data = json_decode($message, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('Failed to decode Redis message', [
                        'error' => json_last_error_msg(),
                        'message' => $message,
                    ]);

                    return;
                }

                ProcessIncomingArticle::dispatch($data);

                $this->info("Dispatched article: {$data['title']}");
            } catch (\Exception $e) {
                Log::error('Failed to process Redis message', [
                    'message' => $message,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }
}
