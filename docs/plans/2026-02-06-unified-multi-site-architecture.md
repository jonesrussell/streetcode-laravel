# Unified Multi-Site Architecture Design

**Date**: 2026-02-06
**Status**: Draft
**Scope**: streetcode.net, orewire.ca, movies-of-war.com, coforge.xyz + North Cloud

---

## 1. Problem Statement

Four Laravel 12 projects share the same tech stack (Vue 3 + Inertia v2 + Tailwind v4 + shadcn-vue) and all consume content from North Cloud, but each implements ingestion, models, and frontend components independently. This leads to:

- Duplicated ingestion logic (3 different subscriber implementations)
- Inconsistent message handling and metadata extraction
- No reusable frontend components across sites
- Painful bootstrapping for new sites (coforge.xyz has nothing)
- Bugs fixed in one site don't propagate to others

---

## 2. Current State Analysis

### 2.1 Ingestion Pipeline Comparison

| Aspect | streetcode.net | orewire.ca | movies-of-war.com | coforge.xyz |
|--------|---------------|------------|-------------------|-------------|
| Command | `articles:subscribe` | `mining:consume` | `articles:subscribe` (package) | None |
| Processing | `ProcessIncomingArticle` job (dispatchSync) | `MiningIngestService` (inline) | `ArticleIngestionService` (package) | None |
| Redis client | Raw `\Redis` (correct) | `Redis::connection()` facade | Raw `\Redis` (correct) | None |
| Multi-channel | Yes (10 crime channels) | No (single `articles:mining`) | No (single channel) | None |
| Signal handling | None | SIGTERM/SIGINT + reconnection | None | None |
| Quality filter | Config-based | None | Config-based | None |
| Dedup | `external_id` check | `news_source_id` + `external_id` | `external_id` check | None |

### 2.2 Model Comparison

| Model | streetcode | orewire | movies-of-war | coforge |
|-------|-----------|---------|---------------|---------|
| Article | `Article` (custom) | `MiningArticle` (custom) | `WarArticle` extends package base | None |
| NewsSource | `NewsSource` | `NewsSource` | `NewsSource` (package) | None |
| Tags | `Tag` (crime types) | N/A (uses Commodity/Category) | `Tag` (package, type=theme) | None |
| Domain models | `City`, `Country`, `Region` | `Company`, `Commodity`, `MiningJurisdiction`, `MiningCategory`, `DrillResult` | `Movie`, `XPost`, `FeaturedSlot` | `User` only |

### 2.3 Frontend Comparison

| Aspect | streetcode | orewire | movies-of-war | coforge |
|--------|-----------|---------|---------------|---------|
| Public layout | `PublicLayout.vue` | Inline in `Home.vue` | `PublicLayout.vue` | None |
| Article card | Used in 6 pages | `MiningArticleCard.vue` | Movie-focused, no article card | None |
| Article feed | `Articles/Index.vue` | `Articles/Index.vue` | N/A | None |
| Theme | Dark red (crime) | Dark amber (mining) | Dark zinc (cinematic) | Default |
| Auth pages | Laravel starter kit | Laravel starter kit | Laravel starter kit | Laravel starter kit |
| shadcn-vue | Full set | Full set | Full set | Full set |

### 2.4 North Cloud Publisher Routing

The publisher uses 5-layer routing. Each site subscribes to different layers:

```
Layer 1: Topic channels         → articles:{topic}
Layer 2: Custom rule channels   → Configurable via DB
Layer 3: Crime channels         → crime:homepage, crime:category:*, crime:courts, crime:context
Layer 4: Location channels      → crime:local:*, crime:province:*, crime:canada, crime:international
Layer 5: Mining channels        → articles:mining
```

**streetcode.net** subscribes to Layers 3+4 (crime + location).
**orewire.ca** subscribes to Layer 5 (mining).
**movies-of-war.com** should subscribe to Layer 1 (`articles:entertainment`, `articles:war`).
**coforge.xyz** TBD — will subscribe to whatever channels match its domain.

---

## 3. Orewire Diagnosis: Why No Content Is Flowing

### Root Causes (ordered by likelihood)

**Issue 1: Production .env not configured for North Cloud Redis**

The `.env.example` shows:
```
NORTHCLOUD_REDIS_HOST=127.0.0.1
NORTHCLOUD_REDIS_PORT=6379
NORTHCLOUD_REDIS_PASSWORD=
```

The defaults point to localhost. On the production server (`deployer@orewire.ca`), the `.env` likely still has these defaults. The North Cloud Redis instance runs on `northcloud.biz`, not localhost.

**Fix**: SSH into `deployer@orewire.ca` and update `.env`:
```bash
NORTHCLOUD_REDIS_HOST=<north-cloud-redis-host>
NORTHCLOUD_REDIS_PORT=<north-cloud-redis-port>
NORTHCLOUD_REDIS_PASSWORD=<north-cloud-redis-password>
```

Use the same values as streetcode.net's production `.env` (they point to the same North Cloud instance).

**Issue 2: systemd service may not be enabled**

The service file exists at `deploy/systemd-user/orewire-mining-consumer.service` but the `deploy.php` doesn't install or restart it. Unlike streetcode's deploy which terminates Horizon for restart, Orewire's deploy only stops SSR.

**Fix**: On the production server:
```bash
# Copy service file
cp ~/orewire-laravel/current/deploy/systemd-user/orewire-mining-consumer.service \
   ~/.config/systemd/user/

# Enable and start
systemctl --user daemon-reload
systemctl --user enable orewire-mining-consumer
systemctl --user start orewire-mining-consumer

# Verify
systemctl --user status orewire-mining-consumer
journalctl --user -u orewire-mining-consumer -f
```

**Issue 3: North Cloud may not have mining-relevant sources**

The publisher only routes to `articles:mining` when the classifier marks an article with `mining.relevance = "core_mining"` or `"peripheral_mining"`. This requires:

1. The North Cloud source-manager has mining news sources configured (e.g., mining.com, northernminer.com, etc.)
2. The crawler is actively crawling those sources
3. The classifier's mining rules/ML model is classifying them

The mining classifier exists (`classifier/internal/classifier/mining_rules.go`) with patterns like `gold mining`, `drill results`, `assay results`, etc. But if no mining sources are being crawled, no articles will be classified.

**Fix**: Check North Cloud dashboard (`northcloud.biz:3002`) to verify mining sources exist. If not, add mining news sources to the source-manager.

**Issue 4: `ConsumeMiningArticles` uses facade instead of raw client**

Orewire's consumer uses `Redis::connection('northcloud')->subscribe()` (the facade), while streetcode uses raw `\Redis` client. The facade approach can add a prefix to pub/sub channels even though the config sets `'prefix' => ''`. This depends on the global `redis.options.prefix` setting.

In Orewire's `config/database.php`:
```php
'options' => [
    'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel')).'-database-'),
],
```

If `APP_NAME=Orewire`, the global prefix becomes `orewire-database-`. Even though the `northcloud` connection has `'prefix' => ''`, the facade may still apply the global prefix to channel names, meaning it subscribes to `orewire-database-articles:mining` instead of `articles:mining`.

**Fix**: Switch to raw `\Redis` client (like streetcode does) or verify the facade respects the per-connection prefix override. The shared package will use raw `\Redis` to avoid this ambiguity.

### Verification Steps

```bash
# 1. SSH to production
ssh deployer@orewire.ca

# 2. Check .env for North Cloud Redis config
grep NORTHCLOUD ~/orewire-laravel/current/.env

# 3. Check if consumer is running
systemctl --user status orewire-mining-consumer

# 4. Test Redis connectivity manually
php -r "
  \$r = new Redis();
  \$r->connect('<northcloud-redis-host>', 6379);
  \$r->auth('<password>');
  echo 'Connected: ' . \$r->ping();
"

# 5. Check if articles:mining channel has any publishers
# (run on North Cloud server)
redis-cli PUBSUB NUMSUB articles:mining
```

---

## 4. Proposed Architecture

### 4.1 Two Shared Packages

```
jonesrussell/northcloud-laravel     (Composer — PHP backend)
@jonesrussell/northcloud-vue        (NPM — Vue frontend components)
```

Both live in separate Git repos and are versioned independently.

### 4.2 Backend Package: `jonesrussell/northcloud-laravel`

**Repository**: `github.com/jonesrussell/northcloud-laravel`

```
northcloud-laravel/
├── src/
│   ├── NorthCloudServiceProvider.php
│   ├── Console/
│   │   └── Commands/
│   │       └── SubscribeToArticleFeed.php    # Unified subscriber
│   ├── Contracts/
│   │   ├── ArticleModel.php                  # Interface
│   │   └── ArticleProcessor.php              # Interface
│   ├── Events/
│   │   ├── ArticleReceived.php               # Pre-processing
│   │   └── ArticleProcessed.php              # Post-processing
│   ├── Jobs/
│   │   └── ProcessIncomingArticle.php         # Dispatches to processor pipeline
│   ├── Models/
│   │   ├── Article.php                        # Abstract base model
│   │   ├── NewsSource.php                     # Concrete model
│   │   └── Tag.php                            # Concrete model
│   ├── Processing/
│   │   └── DefaultArticleProcessor.php        # Standard processing
│   └── Services/
│       ├── ArticleIngestionService.php         # Core ingestion logic
│       └── NewsSourceResolver.php              # Source auto-detection
├── config/
│   └── northcloud.php                          # Published config
├── database/
│   ├── migrations/
│   │   ├── create_news_sources_table.php
│   │   ├── create_articles_table.php
│   │   ├── create_tags_table.php
│   │   └── create_article_tag_table.php
│   └── factories/
│       ├── ArticleFactory.php
│       ├── NewsSourceFactory.php
│       └── TagFactory.php
├── tests/
├── composer.json
└── README.md
```

#### Config: `config/northcloud.php`

```php
return [
    'redis' => [
        'connection' => env('NORTHCLOUD_REDIS_CONNECTION', 'northcloud'),
        'channels' => array_filter(array_map(
            'trim',
            explode(',', env('NORTHCLOUD_CHANNELS', 'articles:default'))
        )),
    ],

    'quality' => [
        'min_score' => (int) env('NORTHCLOUD_MIN_QUALITY_SCORE', 0),
        'enabled' => (bool) env('NORTHCLOUD_QUALITY_FILTER', false),
    ],

    'models' => [
        'article' => \JonesRussell\NorthCloud\Models\Article::class,
        'news_source' => \JonesRussell\NorthCloud\Models\NewsSource::class,
        'tag' => \JonesRussell\NorthCloud\Models\Tag::class,
    ],

    'processors' => [
        \JonesRussell\NorthCloud\Processing\DefaultArticleProcessor::class,
    ],

    'processing' => [
        'sync' => (bool) env('NORTHCLOUD_PROCESS_SYNC', true),
    ],

    'content' => [
        'allowed_tags' => ['p','br','a','strong','em','ul','ol','li','h1','h2','h3','h4','h5','h6'],
    ],

    'tags' => [
        'default_type' => 'topic',
        'auto_create' => true,
        'allowed' => [],
    ],
];
```

#### Subscriber Command: Best of All Three

Takes signal handling from Orewire, multi-channel from Streetcode, raw Redis from Streetcode:

```php
class SubscribeToArticleFeed extends Command
{
    protected $signature = 'articles:subscribe
        {--channels= : Comma-separated channels (overrides config)}
        {--connection= : Redis connection name (overrides config)}
        {--detailed : Show detailed output}';

    // Uses raw \Redis client (no facade prefix issues)
    // Registers SIGTERM/SIGINT handlers for graceful shutdown
    // Reconnects with 5s backoff on connection errors
    // Logs summary stats on shutdown
    // Fires ArticleReceived event before processing
    // Dispatches ProcessIncomingArticle (sync or queued per config)
}
```

#### ArticleProcessor Contract

```php
interface ArticleProcessor
{
    /**
     * Process article data. Return the created/updated model, or null to skip.
     * Throw to abort the pipeline.
     */
    public function process(array $data, ?ArticleModel $existing): ?ArticleModel;

    /**
     * Whether this processor should run for the given data.
     */
    public function shouldProcess(array $data): bool;
}
```

#### Per-Site Processor Examples

**Streetcode — Crime filter + City resolver:**
```php
class CrimeRelevanceFilter implements ArticleProcessor
{
    public function shouldProcess(array $data): bool
    {
        return true; // Always check
    }

    public function process(array $data, ?ArticleModel $existing): ?ArticleModel
    {
        if (($data['crime_relevance'] ?? '') !== 'core_street_crime') {
            return null; // Skip non-crime articles
        }
        return $existing; // Pass through to next processor
    }
}
```

**Orewire — Mining resolver (runs after default processor):**
```php
class MiningArticleEnricher implements ArticleProcessor
{
    public function shouldProcess(array $data): bool
    {
        return isset($data['mining']);
    }

    public function process(array $data, ?ArticleModel $existing): ?ArticleModel
    {
        // Resolve companies, commodities, jurisdictions, drill results
        // Attach to the article created by DefaultArticleProcessor
        $this->companyResolver->resolve($data['companies'] ?? []);
        // ...
        return $existing;
    }
}
```

#### Base Article Model

```php
abstract class Article extends Model implements ArticleModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'news_source_id', 'title', 'slug', 'excerpt', 'content',
        'url', 'external_id', 'image_url', 'author',
        'published_at', 'crawled_at', 'metadata', 'view_count', 'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'crawled_at' => 'datetime',
            'metadata' => 'array',
            'is_featured' => 'boolean',
        ];
    }

    // Relationships to NewsSource and Tags
    // Common scopes: published(), featured(), search(), withTag()
    // Each site extends and adds domain columns + relationships
}
```

### 4.3 Frontend Package: `@jonesrussell/northcloud-vue`

**Repository**: `github.com/jonesrussell/northcloud-vue`

```
northcloud-vue/
├── src/
│   ├── components/
│   │   ├── ArticleCard.vue           # Generic card with badge slot
│   │   ├── ArticleFeed.vue           # Grid layout + pagination
│   │   ├── ArticleDetail.vue         # Full article view
│   │   ├── PublicHeader.vue           # Site header (nav items via props)
│   │   ├── PublicFooter.vue           # Simple footer
│   │   └── SearchBar.vue             # Search with Inertia router
│   ├── layouts/
│   │   └── PublicLayout.vue           # Header + slot + footer
│   ├── composables/
│   │   ├── useArticleFeed.ts          # Pagination, search, filtering
│   │   └── useFormattedDate.ts        # Date formatting
│   ├── types/
│   │   └── index.ts                   # Article, NewsSource, Tag TS types
│   └── index.ts                       # Barrel exports
├── package.json
└── tsconfig.json
```

#### ArticleCard Component

```vue
<script setup lang="ts">
import type { Article } from './types';

interface Props {
  article: Article;
  href: string;
  dateLocale?: string;
}

defineProps<Props>();
</script>

<template>
  <a :href="href" class="block">
    <div class="nc-card">
      <img v-if="article.image_url" :src="article.image_url" :alt="article.title"
           class="nc-card-image" />
      <div class="nc-card-header">
        <span v-if="article.news_source" class="nc-card-source">
          {{ article.news_source.name }}
        </span>
        <span class="nc-card-date">{{ formattedDate }}</span>
      </div>
      <h3 class="nc-card-title">{{ article.title }}</h3>
      <p v-if="article.excerpt" class="nc-card-excerpt">{{ article.excerpt }}</p>
      <!-- Site-specific badges go here -->
      <slot name="badges" :article="article" />
    </div>
  </a>
</template>
```

#### Theming via CSS Custom Properties

Each site sets theme variables in their `app.css`:

```css
/* Package provides defaults */
:root {
  --nc-accent: #3b82f6;        /* blue-500 */
  --nc-accent-hover: #2563eb;  /* blue-600 */
  --nc-bg: #09090b;            /* zinc-950 */
  --nc-bg-card: #18181b;       /* zinc-800 */
  --nc-border: #27272a;        /* zinc-800 */
  --nc-text: #f4f4f5;          /* zinc-100 */
  --nc-text-muted: #a1a1aa;    /* zinc-400 */
}

/* streetcode overrides */
:root { --nc-accent: theme(colors.red.600); }

/* orewire overrides */
:root { --nc-accent: theme(colors.amber.500); }

/* movies-of-war overrides */
:root { --nc-accent: theme(colors.orange.500); }
```

#### TypeScript Types

```typescript
export interface Article {
  id: number;
  news_source_id: number;
  title: string;
  slug: string;
  excerpt: string | null;
  content: string | null;
  url: string;
  external_id: string;
  image_url: string | null;
  author: string | null;
  published_at: string | null;
  crawled_at: string | null;
  metadata: Record<string, unknown>;
  view_count: number;
  is_featured: boolean;
  news_source?: NewsSource;
  tags?: Tag[];
}

export interface NewsSource {
  id: number;
  name: string;
  slug: string;
  url: string;
  is_active: boolean;
}

export interface Tag {
  id: number;
  name: string;
  slug: string;
  type: string;
}
```

---

## 5. .env Convention (All Sites)

```env
# === North Cloud Connection ===
# Same block in every site's .env.example
NORTHCLOUD_REDIS_HOST=127.0.0.1
NORTHCLOUD_REDIS_PORT=6379
NORTHCLOUD_REDIS_PASSWORD=
NORTHCLOUD_REDIS_DB=0

# === Site-Specific Channel Selection ===
# streetcode.net:
NORTHCLOUD_CHANNELS=crime:homepage,crime:category:violent-crime,crime:category:property-crime,crime:category:drug-crime,crime:category:gang-violence,crime:category:organized-crime,crime:category:court-news,crime:category:crime,crime:courts,crime:context

# orewire.ca:
NORTHCLOUD_CHANNELS=articles:mining

# movies-of-war.com:
NORTHCLOUD_CHANNELS=articles:entertainment,articles:war

# coforge.xyz:
NORTHCLOUD_CHANNELS=articles:default

# === Quality Filter (optional) ===
NORTHCLOUD_MIN_QUALITY_SCORE=0
NORTHCLOUD_QUALITY_FILTER=false

# === Processing Mode ===
NORTHCLOUD_PROCESS_SYNC=true
```

---

## 6. Migration Plan

### Phase 1: Create Packages (Week 1)

#### 1a. Create `jonesrussell/northcloud-laravel`

1. Create repo `github.com/jonesrussell/northcloud-laravel`
2. Extract base Article, NewsSource, Tag models from movies-of-war's package + streetcode's models
3. Build unified subscriber command (best patterns from all three)
4. Build `DefaultArticleProcessor` with ingestion service
5. Write migrations (articles, news_sources, tags, article_tag)
6. Write config file
7. Write factories and tests
8. Tag v0.1.0

#### 1b. Create `@jonesrussell/northcloud-vue`

1. Create repo `github.com/jonesrussell/northcloud-vue`
2. Extract ArticleCard from orewire's `MiningArticleCard` (cleanest structure)
3. Extract PublicLayout from streetcode's `PublicLayout.vue`
4. Add composables (useFormattedDate, useArticleFeed)
5. Add TypeScript types
6. Add CSS custom properties theming
7. Build and publish to npm
8. Tag v0.1.0

### Phase 2: Migrate streetcode.net (Week 2)

1. `composer require jonesrussell/northcloud-laravel`
2. Create `App\Models\Article extends \JonesRussell\NorthCloud\Models\Article`
   - Add `city_id` column and City relationship
   - Keep existing migration (don't re-run package migrations)
3. Create `App\NorthCloud\CrimeRelevanceFilter` processor
4. Create `App\NorthCloud\CityResolver` processor
5. Configure `config/northcloud.php`:
   - Set crime channels
   - Register processors: `[CrimeRelevanceFilter, DefaultArticleProcessor, CityResolver]`
6. Delete `app/Console/Commands/SubscribeToArticleFeed.php`
7. Delete `app/Jobs/ProcessIncomingArticle.php`
8. Update systemd service: `ExecStart=/usr/bin/php artisan articles:subscribe`
9. `npm install @jonesrussell/northcloud-vue`
10. Replace inline ArticleCard usage with package component
11. Deploy and verify

### Phase 3: Fix and Migrate orewire.ca (Week 2-3)

**Fix content flow first:**

1. SSH to `deployer@orewire.ca`
2. Set `NORTHCLOUD_REDIS_HOST`, `NORTHCLOUD_REDIS_PORT`, `NORTHCLOUD_REDIS_PASSWORD` in `.env` (copy from streetcode's working production values)
3. Install and enable systemd service
4. Verify content flows with `journalctl --user -u orewire-mining-consumer -f`
5. Check North Cloud dashboard for mining sources; add if missing

**Then migrate to shared package:**

6. `composer require jonesrussell/northcloud-laravel`
7. Create `App\Models\MiningArticle extends \JonesRussell\NorthCloud\Models\Article`
   - Add domain columns: `mining_jurisdiction_id`, `updated_via_ingest_at`
   - Add relationships: commodities, companies, miningCategories, drillResults
8. Create `App\NorthCloud\MiningArticleEnricher` processor (wraps existing resolver logic)
9. Configure `NORTHCLOUD_CHANNELS=articles:mining`
10. Delete `app/Console/Commands/ConsumeMiningArticles.php`
11. Move `MiningIngestService` domain logic into the processor
12. `npm install @jonesrussell/northcloud-vue`
13. Replace `MiningArticleCard` with shared `ArticleCard` + commodity badge slot
14. Deploy and verify

### Phase 4: Migrate movies-of-war.com (Week 3)

1. `composer require jonesrussell/northcloud-laravel`
2. Remove `packages/laravel-redis-articles/` directory
3. Remove path repository from `composer.json`
4. `WarArticle` extends `\JonesRussell\NorthCloud\Models\Article` (add `war_era`)
5. Keep `LinkArticlesToMovies` listener (hooks into `ArticleProcessed` event — same event name)
6. Add North Cloud Redis connection to `config/database.php` and `.env`
7. Configure `NORTHCLOUD_CHANNELS=articles:entertainment,articles:war`
8. Add DDEV daemon config for `articles:subscribe` (replace current one)
9. Deploy and verify

### Phase 5: Bootstrap coforge.xyz (Week 3-4)

1. `composer require jonesrussell/northcloud-laravel`
2. `npm install @jonesrussell/northcloud-vue`
3. `php artisan vendor:publish --provider="JonesRussell\NorthCloud\NorthCloudServiceProvider"`
4. Create `App\Models\Article extends \JonesRussell\NorthCloud\Models\Article` (empty class)
5. `php artisan migrate`
6. Add North Cloud Redis connection to `config/database.php`
7. Configure `.env` with `NORTHCLOUD_CHANNELS=<chosen-channels>`
8. Add `PublicLayout`, `ArticleCard`, `ArticleFeed` from `@jonesrussell/northcloud-vue`
9. Create homepage (`Welcome.vue`) using package components
10. Create `Articles/Index.vue` and `Articles/Show.vue` using package layouts
11. Site is live and receiving articles

---

## 7. What Each Site Keeps (Not Shared)

| Site | Domain-Specific Code |
|------|---------------------|
| streetcode.net | `City`/`Region`/`Country` models, location pages, crime category tags, `articles:reclassify` command, `articles:soft-delete-non-crime` command |
| orewire.ca | `Company`, `Commodity`, `MiningJurisdiction`, `MiningCategory`, `DrillResult` models + resolvers, HTTP ingest API (`MiningIngestController`), metal prices widget |
| movies-of-war.com | `Movie`, `XPost`, `FeaturedSlot` models, TMDB integration, X/Twitter automation, `LinkArticlesToMovies` listener, movie-centric frontend |
| coforge.xyz | TBD — whatever domain this site serves |

---

## 8. Risk Mitigation

**Risk: Breaking existing production sites during migration**
- Mitigation: Migrate one site at a time. Start with streetcode (most mature, best test coverage). Keep old code until new pipeline is verified in production.

**Risk: Package versioning conflicts**
- Mitigation: Use semantic versioning. Pin to `^0.x` during initial development. Sites can upgrade independently.

**Risk: Frontend component doesn't fit all site designs**
- Mitigation: Components use slots and CSS custom properties — maximum flexibility. Sites override only what they need. Ship minimal opinionated styling.

**Risk: North Cloud message format changes break all sites simultaneously**
- Mitigation: Package handles message parsing centrally. Update once, all sites get the fix. This is actually a benefit of the shared package.

---

## 9. Artisan Utility Commands

The `northcloud-laravel` package includes diagnostic and operational commands beyond `articles:subscribe`:

### `articles:status`

Shows the current connection health and subscriber state.

```
$ php artisan articles:status

North Cloud Connection Status
─────────────────────────────
Redis host:      northcloud.biz:6379
Connection:      ✓ Connected (latency: 2ms)
Channels:        crime:homepage, crime:category:violent-crime, ... (10 total)
Quality filter:  enabled (min_score: 60)
Processing mode: sync
Article model:   App\Models\Article
Processors:      CrimeRelevanceFilter → DefaultArticleProcessor → CityResolver

Recent Activity (last 24h)
──────────────────────────
Articles received:  142
Articles processed: 128
Articles skipped:   14 (quality filter: 8, processor reject: 6)
Last received:      2 minutes ago
```

### `articles:test-publish`

Publishes a test article to a channel to verify end-to-end pipeline. Uses a factory-generated article.

```bash
# Publish to first configured channel
php artisan articles:test-publish

# Publish to a specific channel
php artisan articles:test-publish --channel=crime:homepage

# Publish with specific quality score
php artisan articles:test-publish --quality=90

# Dry run — show what would be published without sending
php artisan articles:test-publish --dry-run
```

**Implementation**: Uses a raw `\Redis` client to PUBLISH a JSON message with a unique `id` prefixed `test-`, a generated title, and `publisher.channel` set to the target. The subscriber picks it up normally. The test article is soft-deleted after 5 minutes via a scheduled cleanup.

### `articles:replay`

Re-publishes existing articles back through the processing pipeline. Useful for re-running processors after code changes (e.g., new enrichment logic).

```bash
# Replay all articles from the last 24 hours
php artisan articles:replay --since=24h

# Replay a specific article by ID
php artisan articles:replay --id=42

# Replay with a specific processor only
php artisan articles:replay --processor=CityResolver

# Dry run — show which articles would be replayed
php artisan articles:replay --dry-run
```

**Implementation**: Loads articles from the database, reconstructs the original `$data` array from stored fields + metadata JSON, then dispatches through the processor pipeline. Skips the `DefaultArticleProcessor` (article already exists) unless `--full` is passed.

### `articles:stats`

Displays aggregate statistics about the article corpus.

```
$ php artisan articles:stats

Article Statistics
──────────────────
Total articles:     3,412
Published:          3,105
Soft-deleted:       307

By Source (top 10)
──────────────────
Toronto Star         412
CBC News             387
Global News          341
...

By Tag (top 10)
───────────────
violent-crime        891
property-crime       723
court-news           412
...

Ingestion Rate
──────────────
Today:              142
This week:          847
This month:         3,412
```

```bash
# Filter by date range
php artisan articles:stats --since=7d

# Show only source breakdown
php artisan articles:stats --sources

# JSON output for scripting
php artisan articles:stats --json
```

---

## 10. Success Criteria

- [ ] New site (coforge.xyz) receives articles from North Cloud within 1 hour of setup
- [ ] All four sites use the same `articles:subscribe` command
- [ ] Orewire.ca is receiving mining articles in production
- [ ] Frontend ArticleCard is shared across at least 3 sites
- [ ] No ingestion-related code duplication across sites
