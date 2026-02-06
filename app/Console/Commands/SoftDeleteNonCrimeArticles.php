<?php

namespace App\Console\Commands;

use App\Models\Article;
use Illuminate\Console\Command;

class SoftDeleteNonCrimeArticles extends Command
{
    protected $signature = 'articles:soft-delete-non-crime {--dry-run : Show what would be deleted without deleting}';

    protected $description = 'Soft-delete articles that are not core_street_crime';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $query = Article::whereRaw(
            "JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.crime_relevance')) != ? OR JSON_EXTRACT(metadata, '$.crime_relevance') IS NULL",
            ['core_street_crime']
        );

        $count = $query->count();

        if ($count === 0) {
            $this->info('No non-crime articles found.');

            return Command::SUCCESS;
        }

        if ($dryRun) {
            $this->info("Would soft-delete {$count} non-crime articles.");
            $query->limit(10)->get()->each(function ($article) {
                $this->line("  - [{$article->external_id}] {$article->title}");
            });
            if ($count > 10) {
                $this->line('  ... and '.($count - 10).' more');
            }

            return Command::SUCCESS;
        }

        if (! $this->confirm("Soft-delete {$count} non-crime articles?")) {
            return Command::SUCCESS;
        }

        $deleted = $query->delete();
        $this->info("Soft-deleted {$deleted} non-crime articles.");

        return Command::SUCCESS;
    }
}
