<?php

namespace App\Console\Commands;

use App\Models\Article;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SoftDeleteNonCrimeArticles extends Command
{
    protected $signature = 'articles:soft-delete-non-crime
                            {--dry-run : Show count only, do not soft-delete}';

    protected $description = 'Soft-delete articles that are not from crime channels (metadata.publisher.channel does not start with "crime:")';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $query = Article::query()->whereNull('deleted_at')->where($this->nonCrimeScope());

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
     * Scope: articles whose publisher channel is missing or does not start with "crime:".
     */
    protected function nonCrimeScope(): \Closure
    {
        $driver = DB::connection()->getDriverName();

        return function ($q) use ($driver) {
            if ($driver === 'mysql' || $driver === 'mariadb') {
                $q->whereRaw(
                    "COALESCE(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.publisher.channel')), '') NOT LIKE ?",
                    ['crime:%']
                );
            } elseif ($driver === 'sqlite') {
                $q->whereRaw(
                    "COALESCE(json_extract(metadata, '$.publisher.channel'), '') NOT LIKE ?",
                    ['crime:%']
                );
            } else {
                // PostgreSQL and others: generic JSON path
                $q->whereRaw(
                    "COALESCE(metadata->'publisher'->>'channel', '') NOT LIKE ?",
                    ['crime:%']
                );
            }
        };
    }
}
