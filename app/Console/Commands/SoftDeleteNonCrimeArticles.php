<?php

namespace App\Console\Commands;

use App\Models\Article;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SoftDeleteNonCrimeArticles extends Command
{
    protected $signature = 'articles:soft-delete-non-crime
                            {--dry-run : Show count only, do not soft-delete}';

    protected $description = 'Soft-delete articles that are not from crime channels (Layer 3 crime:* or Layer 1 articles:crime)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $query = Article::query()->whereNull('deleted_at')->where($this->nonCrimeScope());

        if ($dryRun) {
            $this->showChannelBreakdown();
        }

        $count = $query->count();

        if ($count === 0) {
            $this->info('No non-crime articles to soft-delete.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("[DRY RUN] Would soft-delete {$count} article(s). Run without --dry-run to apply.");

            return self::SUCCESS;
        }

        $updated = $query->update(['deleted_at' => now()]);
        $this->info("Soft-deleted {$updated} non-crime article(s).");

        return self::SUCCESS;
    }

    /**
     * Scope: articles whose publisher channel is NOT a crime channel.
     * Crime channels: Layer 3 (crime:*) or Layer 1 (articles:crime).
     */
    protected function nonCrimeScope(): \Closure
    {
        $driver = DB::connection()->getDriverName();

        return function ($q) use ($driver) {
            if ($driver === 'mysql' || $driver === 'mariadb') {
                $q->whereRaw(
                    "COALESCE(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.publisher.channel')), '') NOT LIKE ?
                     AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.publisher.channel')), '') != ?",
                    ['crime:%', 'articles:crime']
                );
            } elseif ($driver === 'sqlite') {
                $q->whereRaw(
                    "COALESCE(json_extract(metadata, '$.publisher.channel'), '') NOT LIKE ?
                     AND COALESCE(json_extract(metadata, '$.publisher.channel'), '') != ?",
                    ['crime:%', 'articles:crime']
                );
            } else {
                // PostgreSQL and others: generic JSON path
                $q->whereRaw(
                    "COALESCE(metadata->'publisher'->>'channel', '') NOT LIKE ?
                     AND COALESCE(metadata->'publisher'->>'channel', '') != ?",
                    ['crime:%', 'articles:crime']
                );
            }
        };
    }

    /**
     * Show breakdown of articles by channel for dry-run.
     */
    protected function showChannelBreakdown(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $channelExpr = "COALESCE(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.publisher.channel')), 'null')";
        } elseif ($driver === 'sqlite') {
            $channelExpr = "COALESCE(json_extract(metadata, '$.publisher.channel'), 'null')";
        } else {
            $channelExpr = "COALESCE(metadata->'publisher'->>'channel', 'null')";
        }

        $channels = Article::query()
            ->whereNull('deleted_at')
            ->selectRaw("{$channelExpr} as channel, COUNT(*) as cnt")
            ->groupBy('channel')
            ->orderBy('cnt', 'desc')
            ->pluck('cnt', 'channel');

        $this->info('Current article channels:');
        foreach ($channels as $channel => $count) {
            $isCrime = str_starts_with($channel, 'crime:') || $channel === 'articles:crime';
            $marker = $isCrime ? '✓' : '✗';
            $this->line("  {$marker} {$channel}: {$count}");
        }
        $this->newLine();
    }
}
