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

        // Editorial/opinion sections (not crime news)
        'Letters to the Editor',
        'Opinion:',
        'OPINION:',
        'Opinions',
        'Editorial:',
        'Column:',
        'Commentary:',

        // Sports sections
        'National Sports',
        'Local Sports',
        'Sports Roundup',
        'Sports Briefs',

        // Website support/donation/meta
        'Support rabble',
        'About rabble',
        'Get Involved',
        'Submit Photo',
        'Submit Video',
        'sponsored',
        'CAMPUS NEWSPAPER',
        'Become a Volunteer',
        'Board Of',
        'Bylaws',
        'How to contribute',
        'Place An Obituary',
        'Submit Letter to the Editor',
        'News Tips',
        'Services - ',
        'help us meet the moment',
        'Terms of Use and Payment',
        'Subscription Agreement',
        'Editorial Policy',
        'Sponsor IndigiNews',
        'IndigiNews Firekeeper',
        'About IndigiNews',
        'Politique d\'utilisation',
        'Politique de confidentialité',
        'Politique éditoriale',
        'À propos',
        'Notre équipe',
        'Prix d\'Excellence',
        'Qui? Quand? Quoi?',

        // Student/campus newspaper columns
        'MACspectations',
        'Sil On The Streets',
        'MSU presidential',
        'MSU Presidentials',
        'SRA passes motion',
        'McMaster plugged in',

        // Sports coverage - additional patterns
        'Equestrian Team',
        'track athlete',
        'soccer rookies',
        'cross-border friendly',

        // Entertainment/events/music
        'An Evening with',
        'Swan Lake',
        'Country Festival',
        'Theatre Aquarius',
        'Fleurs de Villes',
        'Royal Botanical Gardens',
        'Councillor Candidates Debate',
        'Cowboy Carter',
        'Beyoncé',
        'tribute will benefit',
        'Billy Joel tribute',
        'Robbie Burns luncheon',
        'Seniors hosting',

        // Podcast promos
        'podcast episode',
        'Podcast episode',
        'favourite episodes you may have missed',
        'Don\'t Call Me Resilient',
        'Silicon Valley\'s bet on AI',

        // Lifestyle/self-help/health
        'new version of yourself',
        'this semester',
        'emotional whiplash',
        'Instagram is NOT',
        'productivity app',
        'How many books do you read',
        'Spread Christmas cheer',
        'Children\'s Hospice',
        'senior living experience',
        'talk about dementia',
        'Buy early, move better',
        'best window replacement',
        'Farmfair',
        'Celebrating agriculture',
        'How to set healthy boundaries',
        'tips for growing your income',
        'tips from an expert for choosing',
        'self-help book',
        'A new lifeline for anyone travelling',
        'How mentorship fuels',
        'success stories across Nunavut',
        'smallest moments become lasting',

        // Dating/relationship advice
        'tips to deal with disinformation',
        'political polarization in relationships',
        'tips for thriving while being single',
        'Choosing singlehood',
        'racism in an intimate relationship',
        'friendship is treated as essential',
        'beat the winter blues',
        'changing the way we date',
        'in-person dating is making a comeback',
        'Gen Z is struggling with',
        'Is it wrong to date a coworker',
        'Men are embracing beauty culture',

        // Workplace/career (not crime)
        'robot stole my internship',
        'Gen Z\'s entry into the workplace',
        'future of work',
        'Gen Z is burning out',
        'burning out at work',

        // Language/culture columns
        'Slanguage:',

        // Business/finance analysis (not crime)
        'Investigating Airbnb',
        'credit union is building',

        // Media/culture commentary (not crime)
        'Indigenous media makers',
        'assert narrative control',
        'film and social media play recurring',
        'student encampments',
        'what role should our universities',

        // Policy/political opinion (not street crime)
        'sustainable development proposal',
        'reading list for',
        'CERB clawback',
        'Inequality Report',
        'will require nations to stand',
        'modest proposal',
        'survive the Trump',
        'stand up for democracy',
        'hostile to diversity',
        'Truth and Reconciliation',
        'AI regulation',
        'Mark Carney left',
        'left Donald Trump in the dust',
        'Climate misinformation',
        'Fossil-fuel propaganda',
        'stalling climate action',
        'What Cubans want',
        'Lessons from Palestine',
        'resistance of educators',
        'Why America hasn\'t become great',
        'notwithstanding clause',
        'Silicon Valley\'s bet on AI',
        'future of war',

        // International conflicts/war (not street crime)
        'weapon of war',
        'Gazans are paying',
        'Israel-Gaza',
        'war rages in Sudan',
        'As war rages',
        'Répression meurtrière',
        'Femme, Vie, Liberté',
        'Iran target interior minister',
        'crackdown on protesters',

        // Immigration policy (not street crime)
        'protecting people from ICE',
        'Asylum seekers from Gaza',
        'prejudiced policies and bureaucratic',

        // Workplace/labour issues (not street crime)
        'Canada enforces worker safety',
        'worker safety',
        'threats and harassment that dogged',
        'ostrich cull operation',

        // Municipal services/infrastructure (not street crime)
        'warming spaces for',
        'homeless community',
        'water capacity issues',
        'doesn\'t share Waterloo\'s',

        // International incidents (not local street crime)
        'taken\' from B.C. firm\'s Mexican mine',
        'Global Affairs says no Canadians',

        // Political opinion/analysis
        'become great again',
        'ICE protest violence',
        'wine moms',

        // Municipal/city council (not street crime)
        'City looks to save money',
        'Council approves external audit',
        'Hazardous waste project',
        'Coniston community meeting',
        'Coniston application',
        'gateway speed limit program',
        'Traffic signals upgrade',
        'city council attendance',
        'city council members to remain',
        'sharing staff between partners',
        'councillor ordered to pay legal costs',
        'defamation suit falls flat',
        'Leduc censured',
        'objectionable and impertinent',
        'election rule breaches',
        'city scraps the Office of Auditor',
        'Infrastructure gap has council',
        're-thinking reduced water',

        // Awards/media (not street crime)
        'Birth Alerts',
        'New publisher steps in',
        'Latitude 46',

        // French lifestyle/health/opinion articles
        'accès à la propriété',
        'fausse promesse canadienne',
        'Comment survivre au début',
        'Santé reproductive',
        'Mieux vieillir',
        'Le cadeau sous le sapin',
        'Bien-être animal',
        'opacité des abattoirs',
        'Raz-de-marée démocrate',
        'JO de Montréal 1976',
        'consommateurs de la génération Z',
        'Activité physique au bureau',
        'stablecoins présentent des risques',
        'Bloqué au travail',
        'traitement hormonal de la ménopause',
        'mondial junior',
        'JO de Milan-Cortina',
        'hockeyeurs québécois',
        'blessures de la moelle épinière',

        // Website section headers/navigation (exact or near-exact)
        'English Information',
        'En classe',
        'Collaborer',
        'Boutique',
        'Annoncer',
        'Abonnez-vous',
        'Upcoming events',
        'Water Stories',
        'Freelance Guidelines',
        'Who are we?',
        'Our operating model',
        'Our mission',
        'Our Story',
        'About / Contact',
        'How to submit a column',
        'FAQ',
        'Submission Guidelines',
        'Photo & Video Submission',
        'Local Entertainment',
        'Gallery',
        'ensuring the survival of strong local news',
        'Applications for NLFB',
        'Things to do in',
        'What\'s on where',

        // Sports scores/results
        'pick up point in',
        'stay hot in home win',
        'sweep visiting',
        'in interleague road game',
        'dunk king',
        'says Five coach',
        'Come along on a virtual hike',

        // Entertainment/lifestyle
        'debuts solo album',
        'death of Catherine O\'Hara',
        'Reaction to the death of',

        // Environmental policy (not crime)
        'single-use plastics ban',

        // Calgary Herald opinion columns/letters
        'LILLEY:',
        'GUNTER:',
        'BELL:',
        'Braid:',
        'Varcoe:',
        'Letters, Jan.',
        'Letters, Feb.',
        'Letters, Mar.',
        'Letters, Apr.',
        'Letters, May',
        'Letters, Jun.',
        'Letters, Jul.',
        'Letters, Aug.',
        'Letters, Sep.',
        'Letters, Oct.',
        'Letters, Nov.',
        'Letters, Dec.',
        'The Daily FLIP',

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
