<?php

namespace App\Console\Commands;

use App\Models\Article;
use Illuminate\Console\Command;

class ReclassifyArticles extends Command
{
    protected $signature = 'articles:reclassify
        {--dry-run : Show classifications without updating}
        {--force : Skip confirmation prompt}';

    protected $description = 'Backfill crime_relevance for articles missing the field using North Cloud rule-based patterns';

    private const CORE_STREET_CRIME = 'core_street_crime';

    private const PERIPHERAL_CRIME = 'peripheral_crime';

    private const NOT_CRIME = 'not_crime';

    // ── North Cloud exclusion patterns (crime_rules.go) ──────────────────

    private const EXCLUSION_PATTERNS = [
        '/^(Register|Sign up|Login|Subscribe)/i',
        '/^(Listings? By|Directory|Careers|Jobs)/i',
        '/(Part.Time|Full.Time|Hiring|\bPosition\b)/i',
        '/^Local (Sports|Events|Weather)$/i',
    ];

    // ── North Cloud crime patterns (crime_rules.go) ──────────────────────
    // Each entry: [pattern, confidence, crime_type]

    private const VIOLENT_CRIME_PATTERNS = [
        ['/(murder|homicide|manslaughter)/i', 0.95, 'violent_crime'],
        ['/(shooting|shootout|shot dead|shots? fired|fatally shot|gunfire)/i', 0.90, 'violent_crime'],
        ['/(stab|stabbing|stabbed)/i', 0.90, 'violent_crime'],
        ['/(assault|assaulting|assaulted).*(charge[sd]?|arrest|police|pleads?|guilty|sentence|Crown)/i', 0.85, 'violent_crime'],
        ['/(charge[sd]?|arrest|police|pleads?|guilty|convicted).*(assault|assaulting|assaulted)/i', 0.85, 'violent_crime'],
        ['/(sexual assault|sexual exploitation|child exploitation|child sexual|rape|sex assault)/i', 0.90, 'violent_crime'],
        ['/(found dead|human remains)/i', 0.80, 'violent_crime'],
    ];

    private const PROPERTY_CRIME_PATTERNS = [
        ['/(theft|stolen|shoplifting).*(police|arrest)/i', 0.85, 'property_crime'],
        ['/(police|arrest).*(theft|stolen|shoplifting)/i', 0.85, 'property_crime'],
        ['/(burglary|break.in)/i', 0.85, 'property_crime'],
        ['/arson/i', 0.80, 'property_crime'],
        ['/\$[\d,]+.*(stolen|theft)/i', 0.85, 'property_crime'],
    ];

    private const DRUG_CRIME_PATTERNS = [
        ['/(drug bust|drug raid|drug seizure)/i', 0.90, 'drug_crime'],
        ['/(fentanyl|cocaine|heroin).*(seiz|arrest|traffick)/i', 0.90, 'drug_crime'],
    ];

    // ── Supplemental patterns (covers cases NC's ML would have caught) ───

    private const SUPPLEMENTAL_PATTERNS = [
        // Violent — standalone indicators
        ['/(assaulted|assaults)\b/i', 0.80, 'violent_crime'],
        ['/(knife|machete|sword) attack/i', 0.85, 'violent_crime'],
        ['/\bkills (wife|husband|partner|child|son|daughter|mother|father)\b/i', 0.90, 'violent_crime'],
        ['/car attack|vehicle attack|vehicular attack/i', 0.85, 'violent_crime'],
        ['/attack.*(suspect|arrested|police|charged)/i', 0.80, 'violent_crime'],
        ['/(suspect|accused) arrested/i', 0.80, 'violent_crime'],
        ['/(firearm|gun|rifle|shotgun).*(threat|recovered|found|seized)/i', 0.80, 'violent_crime'],
        ['/(threat|recovered|found|seized).*(firearm|gun|rifle|shotgun)/i', 0.80, 'violent_crime'],
        // Violent — contextual
        ['/(intimate.partner|domestic) (violence|assault)/i', 0.85, 'violent_crime'],
        ['/(kidnap|abduct)/i', 0.85, 'violent_crime'],
        ['/(weapons?|firearms?) (charge|offence)/i', 0.80, 'violent_crime'],
        ['/(threatened|threatening).*(death|kill|weapon|knife)/i', 0.80, 'violent_crime'],

        // Property
        ['/(robbery|robbed)/i', 0.85, 'property_crime'],
        ['/(stolen|stole)/i', 0.75, 'property_crime'],
        ['/(fraud|embezzlement|corruption)/i', 0.75, 'property_crime'],
        ['/(mischief|vandalis)/i', 0.75, 'property_crime'],

        // Drug
        ['/(drug offence|drug charge|drug trafficking|drug smuggling)/i', 0.85, 'drug_crime'],
        ['/(cannabis|marijuana|meth|methamphetamine).*(seiz|arrest|charge[sd]?|traffick)/i', 0.85, 'drug_crime'],
        ['/(seiz|confiscat).*(fentanyl|cocaine|heroin|cannabis|drugs)/i', 0.85, 'drug_crime'],
        ['/(drugs?|guns?).*(seizure|seized|confiscated)/i', 0.85, 'drug_crime'],
        ['/(explosive|explosives).*(drugs|guns|weapons)/i', 0.85, 'drug_crime'],
        ['/(pot|marijuana|cannabis|weed).*(police|catch|arrest|charge)/i', 0.80, 'drug_crime'],
        ['/(police|catch|arrest).*(pot|marijuana|smoking)/i', 0.80, 'drug_crime'],

        // Criminal justice (general arrests, charges, sentences)
        ['/(police|officer|OPP|RCMP|SIU|cops).*arrest/i', 0.80, 'criminal_justice'],
        ['/arrest.*(police|officer|OPP|RCMP|cops)/i', 0.80, 'criminal_justice'],
        ['/charged (with|after)/i', 0.80, 'criminal_justice'],
        ['/impaired (driv|operat)/i', 0.80, 'criminal_justice'],
        ['/impaired.*(police|arrest|charge[sd]?|caught)/i', 0.80, 'criminal_justice'],
        ['/(police|arrest|charge[sd]?|caught).*impaired/i', 0.80, 'criminal_justice'],
        ['/sentenced.*(years|prison|jail|penitentiary)/i', 0.75, 'criminal_justice'],
        ['/convicted of/i', 0.75, 'criminal_justice'],
        ['/(flee|fled|fleeing).*(police|scene|officer)/i', 0.75, 'criminal_justice'],
        ['/(wanted|warrant).*(police|arrest|RCMP|OPP)/i', 0.75, 'criminal_justice'],
        ['/(police|RCMP|OPP).*(wanted|warrant)/i', 0.75, 'criminal_justice'],
        ['/pleads? (not )?guilty/i', 0.80, 'criminal_justice'],
        ['/(face[sd]?|facing) (charge[sd]?|consequences|penalties)/i', 0.75, 'criminal_justice'],
        ['/(prohibited|suspended).*(driver|driving)/i', 0.75, 'criminal_justice'],
        ['/(dangerous driving|stunt driving)/i', 0.75, 'criminal_justice'],
        ['/(pre-trial|pretrial) detention/i', 0.75, 'criminal_justice'],
        ['/doesn.t fool (cops|police|officer)/i', 0.70, 'criminal_justice'],
        ['/(could face|may face) charge/i', 0.70, 'criminal_justice'],
        // Fugitive / custody
        ['/manhunt/i', 0.80, 'criminal_justice'],
        ['/escaped (inmate|prisoner|convict|offender)/i', 0.80, 'criminal_justice'],
        ['/wanted.*(caught|found|arrested|hiding|apprehended)/i', 0.80, 'criminal_justice'],
        ['/in custody/i', 0.75, 'criminal_justice'],
        ['/prison (term|sentence)/i', 0.75, 'criminal_justice'],
        ['/arrested after/i', 0.75, 'criminal_justice'],
        ['/(suspect|accused).*(bought|purchased|had).*(rifle|gun|weapon|firearm)/i', 0.75, 'criminal_justice'],
        ['/intercept.*(traffick|smuggl|drugs|fentanyl|cocaine)/i', 0.80, 'criminal_justice'],
    ];

    // ── International patterns (downgrade to peripheral) ─────────────────

    private const INTERNATIONAL_PATTERNS = [
        // From North Cloud crime_rules.go
        '/(Minneapolis|U\.S\.|American|Mexico|European|Israel)/i',
        // Extended for broader international coverage
        '/(Malaysian|Malaysia|1MDB)/i',
        '/(Nigerian|Nigeria|Kenyan|Kenya)/i',
        '/(Norway|Norwegian|Oslo)/i',
        '/(Venezuela|Venezuelan|Maduro)/i',
        '/Former .* leader/i',
    ];

    // ── Context downgrade patterns (awareness/policy/editorial → peripheral) ─

    private const CONTEXT_DOWNGRADE_PATTERNS = [
        '/awareness (week|month|campaign|day)/i',
        '/(support|resources?) for (survivors|victims)/i',
        '/\blegal (advice|support|aid|help)\b/i',
        '/(address|addresses|addressing).*(epidemic|crisis)/i',
        '/(responds?|responding) to .*(every|all) (call|report)/i',
    ];

    private const JUSTICE_PATTERN = '/(charged|arrest|sentenced|trial|convicted|pleads? guilty)/i';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $articles = Article::whereRaw("JSON_EXTRACT(metadata, '$.crime_relevance') IS NULL")->get();

        if ($articles->isEmpty()) {
            $this->info('No articles missing crime_relevance.');

            return Command::SUCCESS;
        }

        $this->info("Found {$articles->count()} articles missing crime_relevance.");

        $results = [
            self::CORE_STREET_CRIME => [],
            self::PERIPHERAL_CRIME => [],
            self::NOT_CRIME => [],
        ];

        foreach ($articles as $article) {
            $classification = $this->classify($article->title);
            $results[$classification['relevance']][] = [
                'article' => $article,
                'confidence' => $classification['confidence'],
                'crime_types' => $classification['crime_types'],
            ];
        }

        $this->displayResults($results);

        if ($dryRun) {
            return Command::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Apply these classifications?')) {
            return Command::SUCCESS;
        }

        $updated = 0;
        foreach ($results as $relevance => $items) {
            foreach ($items as $item) {
                $metadata = $item['article']->metadata ?? [];
                $metadata['crime_relevance'] = $relevance;
                $metadata['crime_relevance_source'] = 'reclassify_rules';
                $metadata['crime_relevance_confidence'] = $item['confidence'];
                if (! empty($item['crime_types'])) {
                    $metadata['crime_types'] = $item['crime_types'];
                }
                $item['article']->metadata = $metadata;
                $item['article']->save();
                $updated++;
            }
        }

        $this->info("Updated {$updated} articles.");

        return Command::SUCCESS;
    }

    /**
     * Classify an article title using North Cloud rule-based patterns + supplemental rules.
     *
     * @return array{relevance: string, confidence: float, crime_types: list<string>}
     */
    private function classify(string $title): array
    {
        if ($this->matchesAny($title, self::EXCLUSION_PATTERNS)) {
            return ['relevance' => self::NOT_CRIME, 'confidence' => 0.95, 'crime_types' => []];
        }

        $relevance = self::NOT_CRIME;
        $confidence = 0.5;
        $crimeTypes = [];

        // North Cloud core patterns
        $this->checkPatterns($title, self::VIOLENT_CRIME_PATTERNS, $relevance, $confidence, $crimeTypes);
        $this->checkPatterns($title, self::PROPERTY_CRIME_PATTERNS, $relevance, $confidence, $crimeTypes);
        $this->checkPatterns($title, self::DRUG_CRIME_PATTERNS, $relevance, $confidence, $crimeTypes);

        // Supplemental patterns (covers what NC's ML classifier would have caught)
        $this->checkPatterns($title, self::SUPPLEMENTAL_PATTERNS, $relevance, $confidence, $crimeTypes);

        // International downgrade
        if ($relevance === self::CORE_STREET_CRIME && $this->matchesAny($title, self::INTERNATIONAL_PATTERNS)) {
            $relevance = self::PERIPHERAL_CRIME;
            $confidence *= 0.7;
        }

        // Context downgrade (awareness/policy/editorial content)
        if ($relevance === self::CORE_STREET_CRIME && $this->matchesAny($title, self::CONTEXT_DOWNGRADE_PATTERNS)) {
            $relevance = self::PERIPHERAL_CRIME;
            $confidence *= 0.7;
        }

        // Add criminal_justice if has crime types and mentions justice terms
        if (! empty($crimeTypes) && preg_match(self::JUSTICE_PATTERN, $title)) {
            if (! in_array('criminal_justice', $crimeTypes)) {
                $crimeTypes[] = 'criminal_justice';
            }
        }

        return ['relevance' => $relevance, 'confidence' => $confidence, 'crime_types' => $crimeTypes];
    }

    /**
     * Check a set of patterns against a title, updating relevance/confidence/crimeTypes by reference.
     *
     * @param  list<array{0: string, 1: float, 2: string}>  $patterns
     * @param  list<string>  $crimeTypes
     */
    private function checkPatterns(string $title, array $patterns, string &$relevance, float &$confidence, array &$crimeTypes): void
    {
        foreach ($patterns as [$pattern, $conf, $crimeType]) {
            if (preg_match($pattern, $title)) {
                $relevance = self::CORE_STREET_CRIME;
                $confidence = max($confidence, $conf);
                if (! in_array($crimeType, $crimeTypes)) {
                    $crimeTypes[] = $crimeType;
                }
            }
        }
    }

    private function matchesAny(string $title, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $title)) {
                return true;
            }
        }

        return false;
    }

    private function displayResults(array $results): void
    {
        foreach ($results as $relevance => $items) {
            if (empty($items)) {
                continue;
            }

            $this->newLine();
            $label = strtoupper(str_replace('_', ' ', $relevance));
            $this->info("{$label} (".count($items).')');

            foreach ($items as $item) {
                $types = implode(', ', $item['crime_types']);
                $conf = number_format($item['confidence'], 2);
                $this->line("  [{$conf}] [{$types}] {$item['article']->title}");
            }
        }

        $this->newLine();
        $total = array_sum(array_map('count', $results));
        $core = count($results[self::CORE_STREET_CRIME]);
        $peripheral = count($results[self::PERIPHERAL_CRIME]);
        $notCrime = count($results[self::NOT_CRIME]);
        $this->info("Summary: {$total} total — {$core} core, {$peripheral} peripheral, {$notCrime} not crime");
    }
}
