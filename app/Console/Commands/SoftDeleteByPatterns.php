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
     * These are intentionally narrow to avoid false positives on crime stories.
     */
    protected array $defaultPatterns = [
        // Website/meta pages (very safe)
        'Work With Us',
        'Work or volunteer',
        'comment policy',
        'Journalistic policy',
        'Privacy Policy',
        'Terms of Service',
        'Pitch an idea',
        'Contact Us',
        'About Us',
        'Advertise with',
        'Subscribe to',
        'Support us',
        'Donate',
        'Newsletter',
        'Sign up for',
        'E Newsletter',

        // Job postings / fellowships
        'Fellowship',
        'Job posting',
        'Hiring now',
        'Join our team',
        'Career Expo',

        // Sports coverage (narrow - won't catch crime stories mentioning sports)
        'hockey game',
        'hockey team',
        'hockey season',
        'hockey tournament',
        'hockey weekend',
        'hockey award',
        'hockey opportunity',
        'men\'s soccer',
        'women\'s soccer',
        'basketball court',
        'boys basketball',
        'girls basketball',
        'football season',
        'Football Association',
        'Golf tournament',
        'tennis tourney',
        'table tennis',
        'Australian Open final',
        'shots at tennis history',
        'Nordic Ski',
        'final score',
        'game recap',
        'match recap',
        'season opener',
        'making the most of college hockey',

        // Weather (safe)
        'snowstorm',
        'weather forecast',
        'drop in temperature',
        'frigid temperatures',
        'blizzard',
        'heat wave',

        // Entertainment
        'movie review',
        'album review',
        'in concert',
        'concert series',
        'Grammy-nominated',
        'Oscar',
        'Emmy-winning',
        'Golden Globe',

        // Holidays/lifestyle (safe)
        'recipe',
        'cooking',
        'gardening',
        'travel guide',
        'vacation',
        'Santa Claus',
        'Christmas Radiothon',
        'holiday travel',
        'holiday campaign',
        'holiday magic',
        'Skill Share',
        'tree-lighting',
        'Living Nativity',
        'Christmas story to life',
        'Christmas gift',
        'Shopping local',
        'Last-minute shoppers',
        'holidays at',
        'during the holidays',
        'over the holidays',
        'holiday Skill Share',
        'for the holidays',
        'Stock up for the holidays',
        'Christmas Carol',
        'know about Christmas',
        'wrapping paper recyclable',

        // Misc junk
        'BUSINESS PAGES',
        'DESI TODAY MAGAZINE',
        'Republic Day',
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
