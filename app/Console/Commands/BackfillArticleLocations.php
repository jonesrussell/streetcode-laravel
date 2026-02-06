<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\City;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackfillArticleLocations extends Command
{
    protected $signature = 'articles:backfill-locations
        {--dry-run : Show what would be updated without making changes}
        {--force : Skip confirmation prompt}';

    protected $description = 'Backfill city_id for articles that have location metadata but no city link';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $articles = Article::query()
            ->whereNull('city_id')
            ->whereNotNull('metadata')
            ->get()
            ->filter(function (Article $article) {
                $metadata = $article->metadata;

                return ! empty($metadata['location_city'])
                    && ! empty($metadata['location_province'])
                    && ! empty($metadata['location_country'])
                    && $metadata['location_country'] !== 'unknown';
            });

        if ($articles->isEmpty()) {
            $this->info('No articles found with location data missing city_id.');

            return Command::SUCCESS;
        }

        $this->info("Found {$articles->count()} articles with location data to backfill.");

        $updated = 0;
        $skipped = 0;

        foreach ($articles as $article) {
            $metadata = $article->metadata;
            $citySlug = $metadata['location_city'];
            $regionCode = $metadata['location_province'];
            $countryName = $metadata['location_country'];

            if ($dryRun) {
                $this->line("  [dry-run] {$article->title} → {$citySlug}, {$regionCode}, {$countryName}");
                $updated++;

                continue;
            }

            try {
                $city = City::findOrCreateFromLocation($citySlug, $regionCode, $countryName);
                $article->update(['city_id' => $city->id]);
                $city->increment('article_count');
                $updated++;

                $this->line("  Updated: {$article->title} → {$city->city_name}");
            } catch (\Exception $e) {
                $skipped++;
                Log::error('Failed to backfill article location', [
                    'article_id' => $article->id,
                    'error' => $e->getMessage(),
                ]);
                $this->warn("  Skipped: {$article->title} — {$e->getMessage()}");
            }
        }

        $this->newLine();
        $label = $dryRun ? 'Would update' : 'Updated';
        $this->info("{$label}: {$updated} articles. Skipped: {$skipped}.");

        if ($dryRun) {
            $this->info('Run without --dry-run to apply changes.');
        }

        return Command::SUCCESS;
    }
}
