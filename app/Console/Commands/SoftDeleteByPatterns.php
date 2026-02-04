<?php

namespace App\Console\Commands;

use App\Models\Article;
use Illuminate\Console\Command;

class SoftDeleteByPatterns extends Command
{
    protected $signature = 'articles:soft-delete-patterns
                            {--dry-run : Show matches only, do not soft-delete}
                            {--pattern=* : Additional patterns to match (case-insensitive)}';

    protected $description = 'Soft-delete articles matching non-crime title patterns';

    /**
     * Default patterns that indicate non-crime content.
     */
    protected array $defaultPatterns = [
        // Website/meta pages
        'Work With Us',
        'Work or volunteer',
        'comment policy',
        'Journalistic policy',
        'Privacy Policy',
        'Terms of Service',
        'Pitch an idea',
        'Contact Us',
        'About Us',
        'Subscribe to',
        'Support us',
        'Donate',
        'Newsletter',
        'Sign up for',

        // Job postings / fellowships
        'Fellowship',
        'Job posting',
        'Career',
        'Hiring',
        'Join our team',

        // Sports
        'Australian Open',
        'NHL',
        'NBA',
        'NFL',
        'Super Bowl',
        'Stanley Cup',
        'World Series',
        'Olympics',
        'Tennis',
        'Hockey',
        'Basketball',
        'Football',
        'Soccer',
        'Golf',
        'Baseball',

        // Weather
        'snowstorm',
        'weather forecast',
        'temperature',
        'blizzard',
        'heat wave',

        // Entertainment
        'movie review',
        'album review',
        'concert',
        'Grammy',
        'Oscar',
        'Emmy',
        'Golden Globe',

        // Lifestyle/misc
        'recipe',
        'cooking',
        'gardening',
        'travel guide',
        'vacation',
        'Santa Claus',
        'Christmas',
        'holiday',
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $extraPatterns = $this->option('pattern') ?: [];
        $patterns = array_merge($this->defaultPatterns, $extraPatterns);

        $this->info('Searching for articles matching '.count($patterns).' patterns...');

        $query = Article::query()->whereNull('deleted_at');

        // Build OR conditions for all patterns
        $query->where(function ($q) use ($patterns) {
            foreach ($patterns as $pattern) {
                $q->orWhere('title', 'LIKE', '%'.$pattern.'%');
            }
        });

        $count = $query->count();

        if ($count === 0) {
            $this->info('No matching articles found.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("[DRY RUN] Would soft-delete {$count} article(s):");
            $this->newLine();

            // Show sample of matches grouped by pattern
            $this->showMatchesByPattern($patterns);

            $this->newLine();
            $this->info('Run without --dry-run to apply.');

            return self::SUCCESS;
        }

        $deleted = $query->update(['deleted_at' => now()]);
        $this->info("Soft-deleted {$deleted} article(s) matching non-crime patterns.");

        return self::SUCCESS;
    }

    protected function showMatchesByPattern(array $patterns): void
    {
        foreach ($patterns as $pattern) {
            $matches = Article::query()
                ->whereNull('deleted_at')
                ->where('title', 'LIKE', '%'.$pattern.'%')
                ->limit(3)
                ->pluck('title');

            if ($matches->isNotEmpty()) {
                $total = Article::query()
                    ->whereNull('deleted_at')
                    ->where('title', 'LIKE', '%'.$pattern.'%')
                    ->count();

                $this->line("<comment>Pattern: \"{$pattern}\" ({$total} matches)</comment>");
                foreach ($matches as $title) {
                    $this->line("  - {$title}");
                }
            }
        }
    }
}
