# NorthCloud Laravel Package Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build `jonesrussell/northcloud-laravel` — a shared Composer package that provides Redis pub/sub article ingestion, base models, and artisan utility commands for all North Cloud-connected Laravel sites.

**Architecture:** Separate Git repo with Orchestra Testbench for testing. Configurable model classes, processor pipeline pattern for per-site customization, raw `\Redis` client for pub/sub (no facade prefix issues), signal handling for graceful shutdown. Sites install via Composer, publish config, extend base models, and register custom processors.

**Tech Stack:** PHP 8.4, Laravel 12, Pest 4, Orchestra Testbench 10, phpredis extension

---

## Prerequisites

Before starting, ensure:
- PHP 8.4+ with phpredis extension installed
- Composer 2.x
- Git configured with GitHub access
- A working directory outside the site repos (e.g., `~/dev/northcloud-laravel/`)

---

### Task 1: Scaffold the Package Repository

**Files:**
- Create: `composer.json`
- Create: `src/.gitkeep` (placeholder, removed later)
- Create: `.gitignore`
- Create: `phpunit.xml`

**Step 1: Create the Git repo and directory structure**

```bash
cd ~/dev
mkdir northcloud-laravel && cd northcloud-laravel
git init
```

**Step 2: Create `composer.json`**

```json
{
    "name": "jonesrussell/northcloud-laravel",
    "description": "Shared article ingestion pipeline for North Cloud-connected Laravel sites",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.4",
        "illuminate/console": "^12.0",
        "illuminate/database": "^12.0",
        "illuminate/events": "^12.0",
        "illuminate/support": "^12.0",
        "ext-redis": "*",
        "ext-json": "*",
        "ext-pcntl": "*"
    },
    "require-dev": {
        "orchestra/testbench": "^10.0",
        "pestphp/pest": "^4.0",
        "pestphp/pest-plugin-laravel": "^4.0",
        "laravel/pint": "^1.24"
    },
    "autoload": {
        "psr-4": {
            "JonesRussell\\NorthCloud\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "JonesRussell\\NorthCloud\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "JonesRussell\\NorthCloud\\NorthCloudServiceProvider"
            ]
        }
    },
    "config": {
        "sort-packages": true,
        "allow-plugins": {
            "pestphp/pest-plugin": true
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

**Step 3: Create `.gitignore`**

```
/vendor/
/node_modules/
.env
.phpunit.result.cache
composer.lock
```

**Step 4: Create `phpunit.xml`**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
</phpunit>
```

**Step 5: Create directory structure**

```bash
mkdir -p src/{Console/Commands,Contracts,Events,Jobs,Models,Processing,Services}
mkdir -p config
mkdir -p database/{migrations,factories}
mkdir -p tests/{Unit,Feature}
```

**Step 6: Install dependencies**

Run: `composer install`
Expected: Dependencies installed successfully, `vendor/` directory created.

**Step 7: Create Pest config**

Create `tests/Pest.php`:

```php
<?php

use JonesRussell\NorthCloud\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');
```

Create `tests/TestCase.php`:

```php
<?php

namespace JonesRussell\NorthCloud\Tests;

use JonesRussell\NorthCloud\NorthCloudServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            NorthCloudServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
```

**Step 8: Commit**

```bash
git add -A
git commit -m "chore: scaffold package with composer, pest, and directory structure"
```

---

### Task 2: Config File and Contracts

**Files:**
- Create: `config/northcloud.php`
- Create: `src/Contracts/ArticleModel.php`
- Create: `src/Contracts/ArticleProcessor.php`

**Step 1: Write the config test**

Create `tests/Unit/ConfigTest.php`:

```php
<?php

it('provides default config values', function () {
    expect(config('northcloud.redis.connection'))->toBe('northcloud');
    expect(config('northcloud.redis.channels'))->toBeArray();
    expect(config('northcloud.quality.min_score'))->toBe(0);
    expect(config('northcloud.models.article'))->toBe(\JonesRussell\NorthCloud\Models\Article::class);
    expect(config('northcloud.processors'))->toBeArray();
    expect(config('northcloud.processing.sync'))->toBeTrue();
    expect(config('northcloud.content.allowed_tags'))->toBeArray();
    expect(config('northcloud.tags.default_type'))->toBe('topic');
});
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Unit/ConfigTest.php -v`
Expected: FAIL — config not registered yet, no service provider.

**Step 3: Create `config/northcloud.php`**

```php
<?php

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
        'allowed_tags' => ['p', 'br', 'a', 'strong', 'em', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
    ],

    'tags' => [
        'default_type' => 'topic',
        'auto_create' => true,
    ],
];
```

**Step 4: Create `src/Contracts/ArticleModel.php`**

```php
<?php

namespace JonesRussell\NorthCloud\Contracts;

interface ArticleModel
{
    public function getExternalId(): string;

    public function getTitle(): string;

    public function getUrl(): ?string;

    public function getStatus(): string;

    public function isPublished(): bool;
}
```

**Step 5: Create `src/Contracts/ArticleProcessor.php`**

```php
<?php

namespace JonesRussell\NorthCloud\Contracts;

use Illuminate\Database\Eloquent\Model;

interface ArticleProcessor
{
    /**
     * Process article data. Return the model to continue the pipeline, or null to skip.
     */
    public function process(array $data, ?Model $article): ?Model;

    /**
     * Whether this processor should run for the given data.
     */
    public function shouldProcess(array $data): bool;
}
```

**Step 6: Create minimal `src/NorthCloudServiceProvider.php`** (just config for now)

```php
<?php

namespace JonesRussell\NorthCloud;

use Illuminate\Support\ServiceProvider;

class NorthCloudServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/northcloud.php', 'northcloud');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/northcloud.php' => config_path('northcloud.php'),
            ], 'northcloud-config');
        }
    }
}
```

**Step 7: Run test to verify it passes**

Run: `vendor/bin/pest tests/Unit/ConfigTest.php -v`
Expected: PASS

**Step 8: Commit**

```bash
git add config/northcloud.php src/Contracts/ src/NorthCloudServiceProvider.php tests/Unit/ConfigTest.php
git commit -m "feat: add config file and ArticleModel/ArticleProcessor contracts"
```

---

### Task 3: Database Migrations

**Files:**
- Create: `database/migrations/2025_01_01_000001_create_news_sources_table.php`
- Create: `database/migrations/2025_01_01_000002_create_tags_table.php`
- Create: `database/migrations/2025_01_01_000003_create_articles_table.php`
- Create: `database/migrations/2025_01_01_000004_create_article_tag_table.php`

**Step 1: Write the migration test**

Create `tests/Feature/MigrationTest.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;

it('creates the news_sources table', function () {
    expect(Schema::hasTable('news_sources'))->toBeTrue();
    expect(Schema::hasColumns('news_sources', [
        'id', 'name', 'slug', 'url', 'is_active', 'metadata',
    ]))->toBeTrue();
});

it('creates the tags table', function () {
    expect(Schema::hasTable('tags'))->toBeTrue();
    expect(Schema::hasColumns('tags', [
        'id', 'name', 'slug', 'type', 'article_count',
    ]))->toBeTrue();
});

it('creates the articles table', function () {
    expect(Schema::hasTable('articles'))->toBeTrue();
    expect(Schema::hasColumns('articles', [
        'id', 'news_source_id', 'title', 'slug', 'excerpt', 'content',
        'url', 'external_id', 'image_url', 'author', 'published_at',
        'crawled_at', 'metadata', 'view_count', 'is_featured', 'deleted_at',
    ]))->toBeTrue();
});

it('creates the article_tag pivot table', function () {
    expect(Schema::hasTable('article_tag'))->toBeTrue();
    expect(Schema::hasColumns('article_tag', [
        'id', 'article_id', 'tag_id', 'confidence',
    ]))->toBeTrue();
});
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/MigrationTest.php -v`
Expected: FAIL — tables don't exist yet.

**Step 3: Create `database/migrations/2025_01_01_000001_create_news_sources_table.php`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('url');
            $table->string('logo_url')->nullable();
            $table->text('description')->nullable();
            $table->integer('credibility_score')->nullable();
            $table->string('bias_rating')->nullable();
            $table->integer('factual_reporting_score')->nullable();
            $table->string('ownership')->nullable();
            $table->string('country')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_sources');
    }
};
```

**Step 4: Create `database/migrations/2025_01_01_000002_create_tags_table.php`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->nullable();
            $table->string('color')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('article_count')->default(0);
            $table->timestamps();

            $table->index(['type', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
```

**Step 5: Create `database/migrations/2025_01_01_000003_create_articles_table.php`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_source_id')->constrained('news_sources')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('url')->unique();
            $table->string('external_id')->nullable()->unique();
            $table->string('image_url')->nullable();
            $table->string('author')->nullable();
            $table->string('status')->default('published')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('crawled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index('news_source_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
```

**Step 6: Create `database/migrations/2025_01_01_000004_create_article_tag_table.php`**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->float('confidence')->nullable();
            $table->timestamps();

            $table->unique(['article_id', 'tag_id']);
            $table->index('tag_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_tag');
    }
};
```

**Step 7: Register migrations in service provider**

Edit `src/NorthCloudServiceProvider.php` — add to `boot()`:

```php
$this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

if ($this->app->runningInConsole()) {
    $this->publishes([
        __DIR__ . '/../config/northcloud.php' => config_path('northcloud.php'),
    ], 'northcloud-config');

    $this->publishes([
        __DIR__ . '/../database/migrations' => database_path('migrations'),
    ], 'northcloud-migrations');
}
```

**Step 8: Run test to verify it passes**

Run: `vendor/bin/pest tests/Feature/MigrationTest.php -v`
Expected: PASS

**Step 9: Commit**

```bash
git add database/migrations/ src/NorthCloudServiceProvider.php tests/Feature/MigrationTest.php
git commit -m "feat: add database migrations for news_sources, articles, tags, article_tag"
```

---

### Task 4: Base Models

**Files:**
- Create: `src/Models/Article.php`
- Create: `src/Models/NewsSource.php`
- Create: `src/Models/Tag.php`
- Create: `database/factories/ArticleFactory.php`
- Create: `database/factories/NewsSourceFactory.php`
- Create: `database/factories/TagFactory.php`

**Step 1: Write model tests**

Create `tests/Unit/Models/ArticleTest.php`:

```php
<?php

use JonesRussell\NorthCloud\Models\Article;
use JonesRussell\NorthCloud\Models\NewsSource;
use JonesRussell\NorthCloud\Models\Tag;

it('belongs to a news source', function () {
    $source = NewsSource::factory()->create();
    $article = Article::factory()->for($source, 'newsSource')->create();

    expect($article->newsSource)->toBeInstanceOf(NewsSource::class);
    expect($article->newsSource->id)->toBe($source->id);
});

it('has many tags with confidence pivot', function () {
    $article = Article::factory()->create();
    $tag = Tag::factory()->create();

    $article->tags()->attach($tag->id, ['confidence' => 0.85]);

    expect($article->tags)->toHaveCount(1);
    expect($article->tags->first()->pivot->confidence)->toBe(0.85);
});

it('scopes to published articles', function () {
    Article::factory()->create(['published_at' => now()->subDay()]);
    Article::factory()->create(['published_at' => null]);

    expect(Article::published()->count())->toBe(1);
});

it('scopes to featured articles', function () {
    Article::factory()->create(['is_featured' => true]);
    Article::factory()->create(['is_featured' => false]);

    expect(Article::featured()->count())->toBe(1);
});

it('casts metadata to array', function () {
    $article = Article::factory()->create(['metadata' => ['quality_score' => 85]]);

    expect($article->metadata)->toBeArray();
    expect($article->metadata['quality_score'])->toBe(85);
});

it('uses soft deletes', function () {
    $article = Article::factory()->create();
    $article->delete();

    expect(Article::count())->toBe(0);
    expect(Article::withTrashed()->count())->toBe(1);
});

it('implements ArticleModel contract', function () {
    $article = Article::factory()->create([
        'external_id' => 'ext-123',
        'title' => 'Test Title',
        'url' => 'https://example.com/test',
        'status' => 'published',
        'published_at' => now(),
    ]);

    expect($article->getExternalId())->toBe('ext-123');
    expect($article->getTitle())->toBe('Test Title');
    expect($article->getUrl())->toBe('https://example.com/test');
    expect($article->getStatus())->toBe('published');
    expect($article->isPublished())->toBeTrue();
});

it('searches articles by keyword', function () {
    Article::factory()->create(['title' => 'Crime wave hits downtown']);
    Article::factory()->create(['title' => 'Weather forecast sunny']);

    expect(Article::search('crime')->count())->toBe(1);
});
```

Create `tests/Unit/Models/NewsSourceTest.php`:

```php
<?php

use JonesRussell\NorthCloud\Models\Article;
use JonesRussell\NorthCloud\Models\NewsSource;

it('has many articles', function () {
    $source = NewsSource::factory()->create();
    Article::factory()->for($source, 'newsSource')->count(3)->create();

    expect($source->articles)->toHaveCount(3);
});

it('scopes to active sources', function () {
    NewsSource::factory()->create(['is_active' => true]);
    NewsSource::factory()->create(['is_active' => false]);

    expect(NewsSource::active()->count())->toBe(1);
});

it('casts metadata to array', function () {
    $source = NewsSource::factory()->create(['metadata' => ['key' => 'value']]);

    expect($source->metadata)->toBeArray();
});
```

Create `tests/Unit/Models/TagTest.php`:

```php
<?php

use JonesRussell\NorthCloud\Models\Article;
use JonesRussell\NorthCloud\Models\Tag;

it('has many articles with confidence pivot', function () {
    $tag = Tag::factory()->create();
    $article = Article::factory()->create();

    $tag->articles()->attach($article->id, ['confidence' => 0.9]);

    expect($tag->articles)->toHaveCount(1);
    expect($tag->articles->first()->pivot->confidence)->toBe(0.9);
});

it('scopes by type', function () {
    Tag::factory()->create(['type' => 'crime']);
    Tag::factory()->create(['type' => 'topic']);

    expect(Tag::type('crime')->count())->toBe(1);
});

it('scopes popular tags', function () {
    Tag::factory()->create(['article_count' => 100]);
    Tag::factory()->create(['article_count' => 50]);
    Tag::factory()->create(['article_count' => 200]);

    $popular = Tag::popular(2)->get();
    expect($popular)->toHaveCount(2);
    expect($popular->first()->article_count)->toBe(200);
});
```

**Step 2: Run tests to verify they fail**

Run: `vendor/bin/pest tests/Unit/Models/ -v`
Expected: FAIL — models don't exist yet.

**Step 3: Create `src/Models/NewsSource.php`**

```php
<?php

namespace JonesRussell\NorthCloud\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use JonesRussell\NorthCloud\Database\Factories\NewsSourceFactory;

class NewsSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'url', 'logo_url', 'description',
        'credibility_score', 'bias_rating', 'factual_reporting_score',
        'ownership', 'country', 'is_active', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function articles(): HasMany
    {
        $articleModel = config('northcloud.models.article', Article::class);

        return $this->hasMany($articleModel);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    protected static function newFactory(): NewsSourceFactory
    {
        return NewsSourceFactory::new();
    }
}
```

**Step 4: Create `src/Models/Tag.php`**

```php
<?php

namespace JonesRussell\NorthCloud\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use JonesRussell\NorthCloud\Database\Factories\TagFactory;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'type', 'color', 'description', 'article_count',
    ];

    public function articles(): BelongsToMany
    {
        $articleModel = config('northcloud.models.article', Article::class);

        return $this->belongsToMany($articleModel, 'article_tag')
            ->withPivot('confidence')
            ->withTimestamps();
    }

    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopePopular($query, int $limit = 10)
    {
        return $query->orderByDesc('article_count')->limit($limit);
    }

    protected static function newFactory(): TagFactory
    {
        return TagFactory::new();
    }
}
```

**Step 5: Create `src/Models/Article.php`**

```php
<?php

namespace JonesRussell\NorthCloud\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use JonesRussell\NorthCloud\Contracts\ArticleModel;
use JonesRussell\NorthCloud\Database\Factories\ArticleFactory;

class Article extends Model implements ArticleModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'news_source_id', 'title', 'slug', 'excerpt', 'content',
        'url', 'external_id', 'image_url', 'author', 'status',
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

    public function newsSource(): BelongsTo
    {
        $newsSourceModel = config('northcloud.models.news_source', NewsSource::class);

        return $this->belongsTo($newsSourceModel);
    }

    public function tags(): BelongsToMany
    {
        $tagModel = config('northcloud.models.tag', Tag::class);

        return $this->belongsToMany($tagModel, 'article_tag')
            ->withPivot('confidence')
            ->withTimestamps();
    }

    // --- ArticleModel contract ---

    public function getExternalId(): string
    {
        return $this->external_id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getStatus(): string
    {
        return $this->status ?? 'draft';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_at !== null;
    }

    // --- Scopes ---

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeWithTag($query, string $tagSlug)
    {
        return $query->whereHas('tags', fn ($q) => $q->where('slug', $tagSlug));
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'LIKE', "%{$term}%")
                ->orWhere('excerpt', 'LIKE', "%{$term}%")
                ->orWhere('content', 'LIKE', "%{$term}%");
        });
    }

    // --- Helpers ---

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    protected static function newFactory(): ArticleFactory
    {
        return ArticleFactory::new();
    }
}
```

**Step 6: Create `database/factories/NewsSourceFactory.php`**

```php
<?php

namespace JonesRussell\NorthCloud\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use JonesRussell\NorthCloud\Models\NewsSource;

class NewsSourceFactory extends Factory
{
    protected $model = NewsSource::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'url' => fake()->unique()->url(),
            'logo_url' => null,
            'description' => fake()->optional()->sentence(),
            'credibility_score' => fake()->numberBetween(40, 95),
            'bias_rating' => fake()->randomElement(['left', 'center-left', 'center', 'center-right', 'right']),
            'factual_reporting_score' => fake()->numberBetween(50, 100),
            'ownership' => fake()->optional()->company(),
            'country' => fake()->countryCode(),
            'is_active' => true,
            'metadata' => [],
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
```

**Step 7: Create `database/factories/TagFactory.php`**

```php
<?php

namespace JonesRussell\NorthCloud\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use JonesRussell\NorthCloud\Models\Tag;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'type' => 'topic',
            'color' => fake()->optional()->hexColor(),
            'description' => fake()->optional()->sentence(),
            'article_count' => 0,
        ];
    }

    public function type(string $type): static
    {
        return $this->state(['type' => $type]);
    }
}
```

**Step 8: Create `database/factories/ArticleFactory.php`**

```php
<?php

namespace JonesRussell\NorthCloud\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use JonesRussell\NorthCloud\Models\Article;
use JonesRussell\NorthCloud\Models\NewsSource;

class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $title = fake()->sentence();

        return [
            'news_source_id' => NewsSource::factory(),
            'title' => $title,
            'slug' => Str::slug($title) . '-' . fake()->unique()->randomNumber(5),
            'excerpt' => fake()->paragraph(),
            'content' => fake()->paragraphs(3, true),
            'url' => fake()->unique()->url(),
            'external_id' => fake()->unique()->uuid(),
            'image_url' => fake()->optional(0.7)->imageUrl(),
            'author' => fake()->optional()->name(),
            'status' => 'published',
            'published_at' => fake()->dateTimeBetween('-30 days'),
            'crawled_at' => now(),
            'metadata' => [],
            'view_count' => fake()->numberBetween(0, 1000),
            'is_featured' => fake()->boolean(10),
        ];
    }

    public function featured(): static
    {
        return $this->state(['is_featured' => true]);
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft', 'published_at' => null]);
    }

    public function published(): static
    {
        return $this->state([
            'status' => 'published',
            'published_at' => fake()->dateTimeBetween('-30 days'),
        ]);
    }
}
```

**Step 9: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Unit/Models/ -v`
Expected: PASS — all model tests green.

**Step 10: Commit**

```bash
git add src/Models/ database/factories/ tests/Unit/Models/
git commit -m "feat: add Article, NewsSource, Tag models with factories and tests"
```

---

### Task 5: NewsSource Resolver Service

**Files:**
- Create: `src/Services/NewsSourceResolver.php`
- Test: `tests/Unit/Services/NewsSourceResolverTest.php`

**Step 1: Write the test**

Create `tests/Unit/Services/NewsSourceResolverTest.php`:

```php
<?php

use JonesRussell\NorthCloud\Models\NewsSource;
use JonesRussell\NorthCloud\Services\NewsSourceResolver;

it('creates a new source from URL', function () {
    $resolver = app(NewsSourceResolver::class);
    $source = $resolver->resolve('https://www.torontostar.com/article/123');

    expect($source)->toBeInstanceOf(NewsSource::class);
    expect($source->slug)->toBe('torontostar-com');
    expect($source->name)->toBe('Torontostar.com');
    expect($source->url)->toBe('https://www.torontostar.com');
});

it('returns existing source on subsequent calls', function () {
    $resolver = app(NewsSourceResolver::class);
    $first = $resolver->resolve('https://www.cbc.ca/news/article-1');
    $second = $resolver->resolve('https://www.cbc.ca/news/article-2');

    expect($first->id)->toBe($second->id);
    expect(NewsSource::count())->toBe(1);
});

it('extracts domain from canonical_url', function () {
    $resolver = app(NewsSourceResolver::class);
    $source = $resolver->resolveFromData([
        'canonical_url' => 'https://globalnews.ca/story/123',
    ]);

    expect($source->slug)->toBe('globalnews-ca');
});

it('falls back through URL fields', function () {
    $resolver = app(NewsSourceResolver::class);

    // No canonical_url, use og_url
    $source = $resolver->resolveFromData([
        'og_url' => 'https://www.reuters.com/article/123',
    ]);
    expect($source->slug)->toBe('reuters-com');

    // No og_url, use source
    $source2 = $resolver->resolveFromData([
        'source' => 'https://www.bbc.com',
    ]);
    expect($source2->slug)->toBe('bbc-com');
});

it('creates unknown source when no URL available', function () {
    $resolver = app(NewsSourceResolver::class);
    $source = $resolver->resolveFromData([]);

    expect($source->slug)->toBe('unknown');
    expect($source->name)->toBe('Unknown Source');
});
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Unit/Services/NewsSourceResolverTest.php -v`
Expected: FAIL — class not found.

**Step 3: Create `src/Services/NewsSourceResolver.php`**

```php
<?php

namespace JonesRussell\NorthCloud\Services;

use Illuminate\Support\Str;

class NewsSourceResolver
{
    /**
     * Resolve a NewsSource from an article URL.
     */
    public function resolve(string $url): mixed
    {
        $domain = $this->extractDomain($url);
        $slug = Str::slug($domain);
        $name = Str::title(str_replace('-', ' ', Str::slug($domain, ' ')));
        $baseUrl = $this->extractBaseUrl($url);

        $model = config('northcloud.models.news_source');

        return $model::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'url' => $baseUrl,
                'is_active' => true,
            ]
        );
    }

    /**
     * Resolve a NewsSource from article data array using URL fallback chain.
     */
    public function resolveFromData(array $data): mixed
    {
        $url = $data['canonical_url']
            ?? $data['og_url']
            ?? $data['source']
            ?? null;

        if ($url) {
            return $this->resolve($url);
        }

        $model = config('northcloud.models.news_source');

        return $model::firstOrCreate(
            ['slug' => 'unknown'],
            [
                'name' => 'Unknown Source',
                'url' => 'https://unknown',
                'is_active' => true,
            ]
        );
    }

    protected function extractDomain(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST) ?? 'unknown';

        // Strip www. prefix
        return preg_replace('/^www\./', '', $host);
    }

    protected function extractBaseUrl(string $url): string
    {
        $parsed = parse_url($url);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? 'unknown';

        return "{$scheme}://{$host}";
    }
}
```

**Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Unit/Services/NewsSourceResolverTest.php -v`
Expected: PASS

**Step 5: Commit**

```bash
git add src/Services/NewsSourceResolver.php tests/Unit/Services/NewsSourceResolverTest.php
git commit -m "feat: add NewsSourceResolver with URL fallback chain"
```

---

### Task 6: Article Ingestion Service

**Files:**
- Create: `src/Services/ArticleIngestionService.php`
- Test: `tests/Feature/ArticleIngestionServiceTest.php`

**Step 1: Write the test**

Create `tests/Feature/ArticleIngestionServiceTest.php`:

```php
<?php

use JonesRussell\NorthCloud\Models\Article;
use JonesRussell\NorthCloud\Models\NewsSource;
use JonesRussell\NorthCloud\Models\Tag;
use JonesRussell\NorthCloud\Services\ArticleIngestionService;

beforeEach(function () {
    $this->service = app(ArticleIngestionService::class);
    $this->validData = [
        'id' => 'ext-article-001',
        'title' => 'Test Crime Article',
        'canonical_url' => 'https://www.torontostar.com/article/test-123',
        'source' => 'https://www.torontostar.com',
        'published_date' => '2026-01-15T10:30:00Z',
        'publisher' => [
            'route_id' => 'route-abc',
            'published_at' => '2026-01-15T10:30:00Z',
            'channel' => 'crime:homepage',
        ],
        'intro' => 'A test excerpt for the article.',
        'body' => '<p>Article body content here.</p>',
        'topics' => ['violent-crime', 'theft'],
        'quality_score' => 85,
    ];
});

it('creates an article from valid data', function () {
    $article = $this->service->ingest($this->validData);

    expect($article)->toBeInstanceOf(Article::class);
    expect($article->title)->toBe('Test Crime Article');
    expect($article->external_id)->toBe('ext-article-001');
    expect($article->excerpt)->toBe('A test excerpt for the article.');
    expect($article->content)->toBe('<p>Article body content here.</p>');
    expect($article->status)->toBe('published');
    expect($article->published_at)->not->toBeNull();
});

it('creates a news source from the article URL', function () {
    $this->service->ingest($this->validData);

    expect(NewsSource::count())->toBe(1);
    expect(NewsSource::first()->slug)->toBe('torontostar-com');
});

it('attaches tags from topics array', function () {
    $article = $this->service->ingest($this->validData);

    expect($article->tags)->toHaveCount(2);
    expect($article->tags->pluck('slug')->sort()->values()->all())->toBe(['theft', 'violent-crime']);
});

it('deduplicates by external_id', function () {
    $this->service->ingest($this->validData);
    $second = $this->service->ingest($this->validData);

    expect($second)->toBeNull();
    expect(Article::count())->toBe(1);
});

it('stores metadata from publisher data', function () {
    $article = $this->service->ingest($this->validData);

    expect($article->metadata)->toBeArray();
    expect($article->metadata['quality_score'])->toBe(85);
    expect($article->metadata['publisher']['channel'])->toBe('crime:homepage');
});

it('sanitizes HTML content', function () {
    $data = array_merge($this->validData, [
        'id' => 'ext-sanitize-test',
        'body' => '<p>Safe</p><script>alert("xss")</script><div>Stripped</div>',
        'canonical_url' => 'https://example.com/sanitize-test',
    ]);

    $article = $this->service->ingest($data);

    expect($article->content)->not->toContain('<script>');
    expect($article->content)->not->toContain('<div>');
    expect($article->content)->toContain('<p>Safe</p>');
});

it('falls back to og_title when title is missing', function () {
    $data = $this->validData;
    unset($data['title']);
    $data['id'] = 'ext-og-title-test';
    $data['og_title'] = 'OG Title Fallback';
    $data['canonical_url'] = 'https://example.com/og-title-test';

    $article = $this->service->ingest($data);

    expect($article->title)->toBe('OG Title Fallback');
});

it('returns null for invalid data', function () {
    $result = $this->service->ingest(['garbage' => 'data']);

    expect($result)->toBeNull();
});
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/ArticleIngestionServiceTest.php -v`
Expected: FAIL — class not found.

**Step 3: Create `src/Services/ArticleIngestionService.php`**

```php
<?php

namespace JonesRussell\NorthCloud\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ArticleIngestionService
{
    public function __construct(
        protected NewsSourceResolver $sourceResolver,
    ) {}

    /**
     * Ingest article data and return the created model, or null if skipped/invalid.
     */
    public function ingest(array $data): ?Model
    {
        if (! $this->validate($data)) {
            return null;
        }

        if ($this->exists($data['id'])) {
            return null;
        }

        $articleModel = config('northcloud.models.article');
        $source = $this->sourceResolver->resolveFromData($data);

        $article = $articleModel::create([
            'news_source_id' => $source->id,
            'title' => $data['title'] ?? $data['og_title'] ?? 'Untitled Article',
            'slug' => $this->generateSlug($data['title'] ?? $data['og_title'] ?? 'untitled'),
            'excerpt' => $data['intro'] ?? $data['og_description'] ?? null,
            'content' => $this->sanitizeContent($data['body'] ?? null),
            'url' => $this->getArticleUrl($data),
            'external_id' => $data['id'],
            'image_url' => $data['og_image'] ?? $data['image_url'] ?? null,
            'author' => $data['author'] ?? null,
            'status' => 'published',
            'published_at' => $this->getPublishedDate($data),
            'crawled_at' => now(),
            'metadata' => $this->buildMetadata($data),
            'view_count' => 0,
            'is_featured' => false,
        ]);

        $this->attachTags($article, $data['topics'] ?? []);

        return $article;
    }

    public function validate(array $data): bool
    {
        if (! isset($data['id'])) {
            return false;
        }

        if (! isset($data['title']) && ! isset($data['og_title'])) {
            return false;
        }

        return true;
    }

    public function exists(string $externalId): bool
    {
        $articleModel = config('northcloud.models.article');

        return $articleModel::where('external_id', $externalId)->exists();
    }

    protected function generateSlug(string $title): string
    {
        $articleModel = config('northcloud.models.article');
        $slug = Str::slug($title);
        $original = $slug;
        $counter = 1;

        while ($articleModel::where('slug', $slug)->exists()) {
            $slug = "{$original}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    protected function getArticleUrl(array $data): string
    {
        return $data['canonical_url']
            ?? $data['og_url']
            ?? $data['source'] ?? 'https://unknown/' . ($data['id'] ?? Str::uuid());
    }

    protected function getPublishedDate(array $data): Carbon
    {
        $dateString = $data['published_date']
            ?? $data['publisher']['published_at']
            ?? null;

        if ($dateString) {
            try {
                $date = Carbon::parse($dateString);
                if ($date->year >= 1970) {
                    return $date;
                }
            } catch (\Exception $e) {
                // Fall through to default
            }
        }

        return now();
    }

    protected function buildMetadata(array $data): array
    {
        $metadata = [];

        if (isset($data['quality_score'])) {
            $metadata['quality_score'] = $data['quality_score'];
        }

        if (isset($data['source_reputation'])) {
            $metadata['source_reputation'] = $data['source_reputation'];
        }

        if (isset($data['publisher'])) {
            $metadata['publisher'] = $data['publisher'];
        }

        if (isset($data['crime_relevance'])) {
            $metadata['crime_relevance'] = $data['crime_relevance'];
        }

        if (isset($data['mining'])) {
            $metadata['mining'] = $data['mining'];
        }

        return $metadata;
    }

    protected function sanitizeContent(?string $content): ?string
    {
        if ($content === null) {
            return null;
        }

        $allowedTags = config('northcloud.content.allowed_tags', []);
        $tagString = implode('', array_map(fn ($tag) => "<{$tag}>", $allowedTags));

        return strip_tags($content, $tagString);
    }

    protected function attachTags(Model $article, array $topics): void
    {
        if (empty($topics)) {
            return;
        }

        $tagModel = config('northcloud.models.tag');
        $defaultType = config('northcloud.tags.default_type', 'topic');
        $autoCreate = config('northcloud.tags.auto_create', true);

        $tagIds = [];
        foreach ($topics as $topic) {
            $slug = Str::slug($topic);

            if ($autoCreate) {
                $tag = $tagModel::firstOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => Str::title(str_replace('-', ' ', $slug)),
                        'type' => $defaultType,
                    ]
                );
                $tagIds[$tag->id] = ['confidence' => null];
            } else {
                $tag = $tagModel::where('slug', $slug)->first();
                if ($tag) {
                    $tagIds[$tag->id] = ['confidence' => null];
                }
            }
        }

        if (! empty($tagIds)) {
            $article->tags()->sync($tagIds);
        }
    }
}
```

**Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Feature/ArticleIngestionServiceTest.php -v`
Expected: PASS

**Step 5: Commit**

```bash
git add src/Services/ArticleIngestionService.php tests/Feature/ArticleIngestionServiceTest.php
git commit -m "feat: add ArticleIngestionService with dedup, sanitization, and tag attachment"
```

---

### Task 7: DefaultArticleProcessor and Pipeline

**Files:**
- Create: `src/Processing/DefaultArticleProcessor.php`
- Create: `src/Processing/ProcessorPipeline.php`
- Test: `tests/Unit/Processing/ProcessorPipelineTest.php`

**Step 1: Write the test**

Create `tests/Unit/Processing/ProcessorPipelineTest.php`:

```php
<?php

use Illuminate\Database\Eloquent\Model;
use JonesRussell\NorthCloud\Contracts\ArticleProcessor;
use JonesRussell\NorthCloud\Models\Article;
use JonesRussell\NorthCloud\Processing\DefaultArticleProcessor;
use JonesRussell\NorthCloud\Processing\ProcessorPipeline;

it('runs default processor to create an article', function () {
    $pipeline = app(ProcessorPipeline::class);

    $data = [
        'id' => 'pipeline-test-001',
        'title' => 'Pipeline Test Article',
        'canonical_url' => 'https://example.com/pipeline-test',
        'publisher' => ['route_id' => 'r1', 'published_at' => '2026-01-15', 'channel' => 'test'],
        'body' => '<p>Content</p>',
    ];

    $result = $pipeline->run($data);

    expect($result)->toBeInstanceOf(Article::class);
    expect($result->title)->toBe('Pipeline Test Article');
});

it('stops pipeline when a processor returns null', function () {
    // Register a custom processor that always rejects
    config(['northcloud.processors' => [RejectAllProcessor::class]]);

    $pipeline = app(ProcessorPipeline::class);

    $data = [
        'id' => 'rejected-001',
        'title' => 'Should Be Rejected',
        'canonical_url' => 'https://example.com/rejected',
        'publisher' => ['route_id' => 'r1', 'published_at' => '2026-01-15', 'channel' => 'test'],
    ];

    $result = $pipeline->run($data);

    expect($result)->toBeNull();
    expect(Article::count())->toBe(0);
});

it('chains multiple processors', function () {
    config(['northcloud.processors' => [
        DefaultArticleProcessor::class,
        AppendMetadataProcessor::class,
    ]]);

    $pipeline = app(ProcessorPipeline::class);

    $data = [
        'id' => 'chain-test-001',
        'title' => 'Chain Test',
        'canonical_url' => 'https://example.com/chain-test',
        'publisher' => ['route_id' => 'r1', 'published_at' => '2026-01-15', 'channel' => 'test'],
    ];

    $result = $pipeline->run($data);

    expect($result)->toBeInstanceOf(Article::class);
    expect($result->fresh()->metadata['enriched'])->toBeTrue();
});

// --- Test helper processors ---

class RejectAllProcessor implements ArticleProcessor
{
    public function process(array $data, ?Model $article): ?Model
    {
        return null;
    }

    public function shouldProcess(array $data): bool
    {
        return true;
    }
}

class AppendMetadataProcessor implements ArticleProcessor
{
    public function process(array $data, ?Model $article): ?Model
    {
        if ($article) {
            $metadata = $article->metadata ?? [];
            $metadata['enriched'] = true;
            $article->update(['metadata' => $metadata]);
        }

        return $article;
    }

    public function shouldProcess(array $data): bool
    {
        return true;
    }
}
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Unit/Processing/ProcessorPipelineTest.php -v`
Expected: FAIL — classes don't exist.

**Step 3: Create `src/Processing/DefaultArticleProcessor.php`**

```php
<?php

namespace JonesRussell\NorthCloud\Processing;

use Illuminate\Database\Eloquent\Model;
use JonesRussell\NorthCloud\Contracts\ArticleProcessor;
use JonesRussell\NorthCloud\Services\ArticleIngestionService;

class DefaultArticleProcessor implements ArticleProcessor
{
    public function __construct(
        protected ArticleIngestionService $ingestionService,
    ) {}

    public function process(array $data, ?Model $article): ?Model
    {
        return $this->ingestionService->ingest($data);
    }

    public function shouldProcess(array $data): bool
    {
        return true;
    }
}
```

**Step 4: Create `src/Processing/ProcessorPipeline.php`**

```php
<?php

namespace JonesRussell\NorthCloud\Processing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use JonesRussell\NorthCloud\Contracts\ArticleProcessor;

class ProcessorPipeline
{
    /**
     * Run the article data through all configured processors.
     */
    public function run(array $data): ?Model
    {
        $processors = config('northcloud.processors', [DefaultArticleProcessor::class]);
        $article = null;

        foreach ($processors as $processorClass) {
            $processor = app($processorClass);

            if (! $processor instanceof ArticleProcessor) {
                Log::warning("Processor {$processorClass} does not implement ArticleProcessor, skipping.");

                continue;
            }

            if (! $processor->shouldProcess($data)) {
                continue;
            }

            $article = $processor->process($data, $article);

            if ($article === null) {
                return null;
            }
        }

        return $article;
    }
}
```

**Step 5: Run test to verify it passes**

Run: `vendor/bin/pest tests/Unit/Processing/ProcessorPipelineTest.php -v`
Expected: PASS

**Step 6: Commit**

```bash
git add src/Processing/ tests/Unit/Processing/
git commit -m "feat: add DefaultArticleProcessor and ProcessorPipeline"
```

---

### Task 8: Events

**Files:**
- Create: `src/Events/ArticleReceived.php`
- Create: `src/Events/ArticleProcessed.php`
- Test: `tests/Unit/Events/EventTest.php`

**Step 1: Write the test**

Create `tests/Unit/Events/EventTest.php`:

```php
<?php

use Illuminate\Support\Facades\Event;
use JonesRussell\NorthCloud\Events\ArticleProcessed;
use JonesRussell\NorthCloud\Events\ArticleReceived;
use JonesRussell\NorthCloud\Models\Article;

it('creates ArticleReceived event with data and channel', function () {
    $event = new ArticleReceived(['id' => 'test'], 'crime:homepage');

    expect($event->articleData)->toBe(['id' => 'test']);
    expect($event->channel)->toBe('crime:homepage');
});

it('creates ArticleProcessed event with article model', function () {
    $article = Article::factory()->create();
    $event = new ArticleProcessed($article);

    expect($event->article)->toBeInstanceOf(Article::class);
    expect($event->article->id)->toBe($article->id);
});

it('dispatches ArticleReceived as a standard Laravel event', function () {
    Event::fake();

    ArticleReceived::dispatch(['id' => 'evt-test'], 'test-channel');

    Event::assertDispatched(ArticleReceived::class, function ($event) {
        return $event->articleData['id'] === 'evt-test' && $event->channel === 'test-channel';
    });
});
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Unit/Events/EventTest.php -v`
Expected: FAIL — event classes don't exist.

**Step 3: Create `src/Events/ArticleReceived.php`**

```php
<?php

namespace JonesRussell\NorthCloud\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ArticleReceived
{
    use Dispatchable;

    public function __construct(
        public array $articleData,
        public string $channel,
    ) {}
}
```

**Step 4: Create `src/Events/ArticleProcessed.php`**

```php
<?php

namespace JonesRussell\NorthCloud\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

class ArticleProcessed
{
    use Dispatchable;

    public function __construct(
        public Model $article,
    ) {}
}
```

**Step 5: Run test to verify it passes**

Run: `vendor/bin/pest tests/Unit/Events/EventTest.php -v`
Expected: PASS

**Step 6: Commit**

```bash
git add src/Events/ tests/Unit/Events/
git commit -m "feat: add ArticleReceived and ArticleProcessed events"
```

---

### Task 9: ProcessIncomingArticle Job

**Files:**
- Create: `src/Jobs/ProcessIncomingArticle.php`
- Test: `tests/Feature/ProcessIncomingArticleJobTest.php`

**Step 1: Write the test**

Create `tests/Feature/ProcessIncomingArticleJobTest.php`:

```php
<?php

use Illuminate\Support\Facades\Event;
use JonesRussell\NorthCloud\Events\ArticleProcessed;
use JonesRussell\NorthCloud\Jobs\ProcessIncomingArticle;
use JonesRussell\NorthCloud\Models\Article;

it('processes valid article data into a database record', function () {
    $data = [
        'id' => 'job-test-001',
        'title' => 'Job Test Article',
        'canonical_url' => 'https://example.com/job-test',
        'publisher' => ['route_id' => 'r1', 'published_at' => '2026-01-15', 'channel' => 'test'],
        'body' => '<p>Content</p>',
        'topics' => ['crime'],
    ];

    ProcessIncomingArticle::dispatchSync($data);

    expect(Article::count())->toBe(1);
    expect(Article::first()->title)->toBe('Job Test Article');
});

it('fires ArticleProcessed event after processing', function () {
    Event::fake([ArticleProcessed::class]);

    $data = [
        'id' => 'job-event-001',
        'title' => 'Event Test',
        'canonical_url' => 'https://example.com/event-test',
        'publisher' => ['route_id' => 'r1', 'published_at' => '2026-01-15', 'channel' => 'test'],
    ];

    ProcessIncomingArticle::dispatchSync($data);

    Event::assertDispatched(ArticleProcessed::class);
});

it('does not fire event when article is skipped', function () {
    Event::fake([ArticleProcessed::class]);

    // First call creates the article
    ProcessIncomingArticle::dispatchSync([
        'id' => 'job-dup-001',
        'title' => 'Duplicate Test',
        'canonical_url' => 'https://example.com/dup-test',
        'publisher' => ['route_id' => 'r1', 'published_at' => '2026-01-15', 'channel' => 'test'],
    ]);

    Event::assertDispatchedTimes(ArticleProcessed::class, 1);

    // Second call should be skipped (duplicate)
    ProcessIncomingArticle::dispatchSync([
        'id' => 'job-dup-001',
        'title' => 'Duplicate Test',
        'canonical_url' => 'https://example.com/dup-test',
        'publisher' => ['route_id' => 'r1', 'published_at' => '2026-01-15', 'channel' => 'test'],
    ]);

    Event::assertDispatchedTimes(ArticleProcessed::class, 1);
});
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/ProcessIncomingArticleJobTest.php -v`
Expected: FAIL — class not found.

**Step 3: Create `src/Jobs/ProcessIncomingArticle.php`**

```php
<?php

namespace JonesRussell\NorthCloud\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use JonesRussell\NorthCloud\Events\ArticleProcessed;
use JonesRussell\NorthCloud\Processing\ProcessorPipeline;

class ProcessIncomingArticle implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public array $articleData,
    ) {}

    public function handle(ProcessorPipeline $pipeline): void
    {
        $startTime = microtime(true);

        try {
            $article = $pipeline->run($this->articleData);

            if ($article) {
                ArticleProcessed::dispatch($article);

                $elapsed = round((microtime(true) - $startTime) * 1000, 1);
                Log::info('Article processed', [
                    'external_id' => $this->articleData['id'] ?? 'unknown',
                    'title' => $article->title,
                    'elapsed_ms' => $elapsed,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to process article', [
                'external_id' => $this->articleData['id'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
```

**Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Feature/ProcessIncomingArticleJobTest.php -v`
Expected: PASS

**Step 5: Commit**

```bash
git add src/Jobs/ProcessIncomingArticle.php tests/Feature/ProcessIncomingArticleJobTest.php
git commit -m "feat: add ProcessIncomingArticle job with event dispatching"
```

---

### Task 10: Subscribe Command

**Files:**
- Create: `src/Console/Commands/SubscribeToArticleFeed.php`
- Test: `tests/Feature/SubscribeCommandTest.php`

**Step 1: Write the test**

Note: Testing a pub/sub subscriber is difficult in unit tests because it blocks. We test the command setup, message processing, and validation — not the actual Redis subscription.

Create `tests/Feature/SubscribeCommandTest.php`:

```php
<?php

use JonesRussell\NorthCloud\Console\Commands\SubscribeToArticleFeed;
use JonesRussell\NorthCloud\Models\Article;

it('registers the articles:subscribe command', function () {
    $this->artisan('list')
        ->expectsOutputToContain('articles:subscribe');
});

it('resolves channels from config', function () {
    config(['northcloud.redis.channels' => ['crime:homepage', 'crime:courts']]);

    $command = app(SubscribeToArticleFeed::class);

    $reflection = new ReflectionMethod($command, 'resolveChannels');
    $channels = $reflection->invoke($command);

    expect($channels)->toBe(['crime:homepage', 'crime:courts']);
});

it('processes a valid message into an article', function () {
    $command = app(SubscribeToArticleFeed::class);

    $message = json_encode([
        'id' => 'cmd-test-001',
        'title' => 'Command Test Article',
        'canonical_url' => 'https://example.com/cmd-test',
        'publisher' => ['route_id' => 'r1', 'published_at' => '2026-01-15', 'channel' => 'test'],
        'body' => '<p>Content</p>',
    ]);

    $reflection = new ReflectionMethod($command, 'processMessage');
    $reflection->invoke($command, $message);

    expect(Article::count())->toBe(1);
    expect(Article::first()->title)->toBe('Command Test Article');
});

it('skips messages with low quality score when filter is enabled', function () {
    config([
        'northcloud.quality.enabled' => true,
        'northcloud.quality.min_score' => 60,
    ]);

    $command = app(SubscribeToArticleFeed::class);

    $message = json_encode([
        'id' => 'low-quality-001',
        'title' => 'Low Quality Article',
        'canonical_url' => 'https://example.com/low-quality',
        'publisher' => ['route_id' => 'r1', 'published_at' => '2026-01-15', 'channel' => 'test'],
        'quality_score' => 30,
    ]);

    $reflection = new ReflectionMethod($command, 'processMessage');
    $reflection->invoke($command, $message);

    expect(Article::count())->toBe(0);
});

it('rejects invalid JSON messages', function () {
    $command = app(SubscribeToArticleFeed::class);

    $reflection = new ReflectionMethod($command, 'processMessage');
    $reflection->invoke($command, 'not-json{{{');

    expect(Article::count())->toBe(0);
});
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/SubscribeCommandTest.php -v`
Expected: FAIL — class not found.

**Step 3: Create `src/Console/Commands/SubscribeToArticleFeed.php`**

```php
<?php

namespace JonesRussell\NorthCloud\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use JonesRussell\NorthCloud\Events\ArticleReceived;
use JonesRussell\NorthCloud\Jobs\ProcessIncomingArticle;

class SubscribeToArticleFeed extends Command
{
    protected $signature = 'articles:subscribe
        {--channels= : Comma-separated channels (overrides config)}
        {--connection= : Redis connection name (overrides config)}
        {--detailed : Show detailed output}';

    protected $description = 'Subscribe to North Cloud Redis pub/sub for incoming articles';

    protected bool $shouldStop = false;

    protected int $processedCount = 0;

    protected int $skippedCount = 0;

    protected int $errorCount = 0;

    public function handle(): void
    {
        $channels = $this->resolveChannels();
        $connection = $this->option('connection') ?? config('northcloud.redis.connection', 'northcloud');

        $this->info("Connection: {$connection}");
        $this->info('Subscribing to channels: ' . implode(', ', $channels));

        $this->registerSignalHandlers();

        $redisConfig = config("database.redis.{$connection}");

        if (! $redisConfig) {
            $this->error("Redis connection [{$connection}] not configured in database.redis.");

            return;
        }

        while (! $this->shouldStop) {
            try {
                $client = $this->createRedisClient($redisConfig);

                $client->subscribe($channels, function (\Redis $redis, string $channel, string $message) {
                    $this->processMessage($message);
                });
            } catch (\RedisException $e) {
                if ($this->shouldStop) {
                    break;
                }

                $msg = $e->getMessage();
                if (str_contains($msg, 'read error') || str_contains($msg, 'timed out')) {
                    // Expected read timeout — reconnect loop continues
                    continue;
                }

                $this->error("Redis error: {$msg}. Reconnecting in 5s...");
                Log::error('Redis subscriber error', ['error' => $msg]);
                sleep(5);
            } catch (\Exception $e) {
                if ($this->shouldStop) {
                    break;
                }

                $this->error("Unexpected error: {$e->getMessage()}. Reconnecting in 5s...");
                Log::error('Subscriber unexpected error', ['error' => $e->getMessage()]);
                sleep(5);
            }
        }

        $this->displaySummary();
    }

    protected function resolveChannels(): array
    {
        if ($channels = $this->option('channels')) {
            return array_map('trim', explode(',', $channels));
        }

        return config('northcloud.redis.channels', ['articles:default']);
    }

    protected function processMessage(string $message): void
    {
        try {
            $data = json_decode($message, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to decode Redis message', [
                    'error' => json_last_error_msg(),
                    'message_preview' => substr($message, 0, 200),
                ]);
                $this->errorCount++;

                return;
            }

            if (! $this->isValidMessage($data)) {
                Log::warning('Invalid article message format', ['data_keys' => array_keys($data)]);
                $this->errorCount++;

                return;
            }

            // Quality filter
            $qualityEnabled = config('northcloud.quality.enabled', false);
            $minScore = config('northcloud.quality.min_score', 0);

            if ($qualityEnabled && $minScore > 0 && ($data['quality_score'] ?? 0) < $minScore) {
                $this->skippedCount++;

                if ($this->option('detailed')) {
                    $this->line("  Skipped (quality {$data['quality_score']} < {$minScore}): {$data['title']}");
                }

                return;
            }

            $title = $data['title'] ?? $data['og_title'] ?? 'Untitled';
            ArticleReceived::dispatch($data, $data['publisher']['channel'] ?? 'unknown');

            // Dispatch job (sync or queued per config)
            $sync = config('northcloud.processing.sync', true);
            if ($sync) {
                ProcessIncomingArticle::dispatchSync($data);
            } else {
                ProcessIncomingArticle::dispatch($data);
            }

            $this->processedCount++;

            if ($this->option('detailed')) {
                $this->info("  Processed: {$title}");
            }
        } catch (\Exception $e) {
            $this->errorCount++;
            Log::error('Failed to process message', [
                'error' => $e->getMessage(),
                'message_preview' => substr($message, 0, 200),
            ]);
        }
    }

    protected function isValidMessage(array $data): bool
    {
        if (! isset($data['id'])) {
            return false;
        }

        if (! isset($data['title']) && ! isset($data['og_title'])) {
            return false;
        }

        return true;
    }

    protected function createRedisClient(array $config): \Redis
    {
        $client = new \Redis;

        $host = $config['host'] ?? '127.0.0.1';
        $port = (int) ($config['port'] ?? 6379);
        $password = $config['password'] ?? null;

        $client->connect($host, $port);

        if ($password) {
            $client->auth($password);
        }

        $readTimeout = $config['read_timeout'] ?? -1;
        $client->setOption(\Redis::OPT_READ_TIMEOUT, (float) $readTimeout);

        return $client;
    }

    protected function registerSignalHandlers(): void
    {
        if (! extension_loaded('pcntl')) {
            return;
        }

        pcntl_async_signals(true);

        pcntl_signal(SIGTERM, function () {
            $this->info('Received SIGTERM, shutting down gracefully...');
            $this->shouldStop = true;
        });

        pcntl_signal(SIGINT, function () {
            $this->info('Received SIGINT, shutting down gracefully...');
            $this->shouldStop = true;
        });
    }

    protected function displaySummary(): void
    {
        $this->newLine();
        $this->info('Subscriber shutdown summary:');
        $this->info("  Processed: {$this->processedCount}");
        $this->info("  Skipped:   {$this->skippedCount}");
        $this->info("  Errors:    {$this->errorCount}");
    }
}
```

**Step 4: Register command in service provider**

Edit `src/NorthCloudServiceProvider.php` — add to `boot()`:

```php
if ($this->app->runningInConsole()) {
    $this->commands([
        Console\Commands\SubscribeToArticleFeed::class,
    ]);

    // ... existing publishes() calls
}
```

**Step 5: Run test to verify it passes**

Run: `vendor/bin/pest tests/Feature/SubscribeCommandTest.php -v`
Expected: PASS

**Step 6: Commit**

```bash
git add src/Console/Commands/SubscribeToArticleFeed.php tests/Feature/SubscribeCommandTest.php src/NorthCloudServiceProvider.php
git commit -m "feat: add articles:subscribe command with signal handling and reconnection"
```

---

### Task 11: `articles:status` Command

**Files:**
- Create: `src/Console/Commands/ArticlesStatus.php`
- Test: `tests/Feature/ArticlesStatusCommandTest.php`

**Step 1: Write the test**

Create `tests/Feature/ArticlesStatusCommandTest.php`:

```php
<?php

use JonesRussell\NorthCloud\Models\Article;

it('registers the articles:status command', function () {
    $this->artisan('list')
        ->expectsOutputToContain('articles:status');
});

it('displays configured channels', function () {
    config(['northcloud.redis.channels' => ['crime:homepage', 'crime:courts']]);

    $this->artisan('articles:status')
        ->expectsOutputToContain('crime:homepage')
        ->expectsOutputToContain('crime:courts');
});

it('displays recent activity counts', function () {
    Article::factory()->count(5)->create([
        'created_at' => now()->subHours(2),
    ]);
    Article::factory()->count(3)->create([
        'created_at' => now()->subDays(2),
    ]);

    $this->artisan('articles:status')
        ->expectsOutputToContain('5');
});

it('displays processing mode', function () {
    config(['northcloud.processing.sync' => true]);

    $this->artisan('articles:status')
        ->expectsOutputToContain('sync');
});
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/ArticlesStatusCommandTest.php -v`
Expected: FAIL — command not registered.

**Step 3: Create `src/Console/Commands/ArticlesStatus.php`**

```php
<?php

namespace JonesRussell\NorthCloud\Console\Commands;

use Illuminate\Console\Command;

class ArticlesStatus extends Command
{
    protected $signature = 'articles:status';

    protected $description = 'Show North Cloud connection status and recent activity';

    public function handle(): int
    {
        $this->connectionStatus();
        $this->newLine();
        $this->recentActivity();

        return self::SUCCESS;
    }

    protected function connectionStatus(): void
    {
        $connection = config('northcloud.redis.connection', 'northcloud');
        $channels = config('northcloud.redis.channels', []);
        $qualityEnabled = config('northcloud.quality.enabled', false);
        $minScore = config('northcloud.quality.min_score', 0);
        $sync = config('northcloud.processing.sync', true);
        $articleModel = config('northcloud.models.article');
        $processors = config('northcloud.processors', []);

        $this->info('North Cloud Connection Status');
        $this->line(str_repeat('─', 40));

        // Test Redis connection
        $redisConfig = config("database.redis.{$connection}");
        if ($redisConfig) {
            $host = $redisConfig['host'] ?? '127.0.0.1';
            $port = $redisConfig['port'] ?? 6379;
            $this->line("Redis host:      {$host}:{$port}");

            try {
                $client = new \Redis;
                $start = microtime(true);
                $client->connect($host, (int) $port, 2);
                if ($password = $redisConfig['password'] ?? null) {
                    $client->auth($password);
                }
                $client->ping();
                $latency = round((microtime(true) - $start) * 1000);
                $this->line("Connection:      <fg=green>Connected</> (latency: {$latency}ms)");
                $client->close();
            } catch (\Exception $e) {
                $this->line("Connection:      <fg=red>Failed</> ({$e->getMessage()})");
            }
        } else {
            $this->line("Redis host:      <fg=red>Not configured</>");
        }

        $this->line('Channels:        ' . implode(', ', $channels) . ' (' . count($channels) . ' total)');

        if ($qualityEnabled) {
            $this->line("Quality filter:  enabled (min_score: {$minScore})");
        } else {
            $this->line('Quality filter:  disabled');
        }

        $this->line('Processing mode: ' . ($sync ? 'sync' : 'queued'));
        $this->line("Article model:   {$articleModel}");

        if (! empty($processors)) {
            $names = array_map(fn ($p) => class_basename($p), $processors);
            $this->line('Processors:      ' . implode(' → ', $names));
        }
    }

    protected function recentActivity(): void
    {
        $articleModel = config('northcloud.models.article');

        $this->info('Recent Activity (last 24h)');
        $this->line(str_repeat('─', 40));

        $last24h = $articleModel::where('created_at', '>=', now()->subDay())->count();
        $last7d = $articleModel::where('created_at', '>=', now()->subWeek())->count();
        $total = $articleModel::count();

        $this->line("Articles (24h):  {$last24h}");
        $this->line("Articles (7d):   {$last7d}");
        $this->line("Articles total:  {$total}");

        $latest = $articleModel::latest('created_at')->first();
        if ($latest) {
            $ago = $latest->created_at->diffForHumans();
            $this->line("Last received:   {$ago}");
        } else {
            $this->line('Last received:   never');
        }
    }
}
```

**Step 4: Register command in service provider**

Add `Console\Commands\ArticlesStatus::class` to the `$this->commands()` array in `NorthCloudServiceProvider`.

**Step 5: Run test to verify it passes**

Run: `vendor/bin/pest tests/Feature/ArticlesStatusCommandTest.php -v`
Expected: PASS

**Step 6: Commit**

```bash
git add src/Console/Commands/ArticlesStatus.php tests/Feature/ArticlesStatusCommandTest.php src/NorthCloudServiceProvider.php
git commit -m "feat: add articles:status command showing connection health and activity"
```

---

### Task 12: `articles:stats` Command

**Files:**
- Create: `src/Console/Commands/ArticlesStats.php`
- Test: `tests/Feature/ArticlesStatsCommandTest.php`

**Step 1: Write the test**

Create `tests/Feature/ArticlesStatsCommandTest.php`:

```php
<?php

use JonesRussell\NorthCloud\Models\Article;
use JonesRussell\NorthCloud\Models\NewsSource;
use JonesRussell\NorthCloud\Models\Tag;

it('registers the articles:stats command', function () {
    $this->artisan('list')
        ->expectsOutputToContain('articles:stats');
});

it('displays total article count', function () {
    Article::factory()->count(5)->create();

    $this->artisan('articles:stats')
        ->expectsOutputToContain('5');
});

it('displays articles by source', function () {
    $source = NewsSource::factory()->create(['name' => 'Toronto Star']);
    Article::factory()->for($source, 'newsSource')->count(3)->create();

    $this->artisan('articles:stats --sources')
        ->expectsOutputToContain('Toronto Star')
        ->expectsOutputToContain('3');
});

it('outputs JSON format', function () {
    Article::factory()->count(2)->create();

    $this->artisan('articles:stats --json')
        ->expectsOutputToContain('"total"');
});
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/ArticlesStatsCommandTest.php -v`
Expected: FAIL

**Step 3: Create `src/Console/Commands/ArticlesStats.php`**

```php
<?php

namespace JonesRussell\NorthCloud\Console\Commands;

use Illuminate\Console\Command;

class ArticlesStats extends Command
{
    protected $signature = 'articles:stats
        {--since= : Time period (e.g., 7d, 24h, 30d)}
        {--sources : Show only source breakdown}
        {--tags : Show only tag breakdown}
        {--json : Output as JSON}';

    protected $description = 'Display aggregate article statistics';

    public function handle(): int
    {
        $articleModel = config('northcloud.models.article');
        $newsSourceModel = config('northcloud.models.news_source');
        $tagModel = config('northcloud.models.tag');

        $since = $this->parseSince($this->option('since'));

        $query = $articleModel::query();
        if ($since) {
            $query->where('created_at', '>=', $since);
        }

        $total = $query->count();
        $softDeleted = $articleModel::onlyTrashed()
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
            ->count();

        if ($this->option('json')) {
            return $this->outputJson($articleModel, $newsSourceModel, $tagModel, $total, $softDeleted, $since);
        }

        if ($this->option('sources')) {
            $this->displaySourceBreakdown($articleModel, $since);

            return self::SUCCESS;
        }

        if ($this->option('tags')) {
            $this->displayTagBreakdown($tagModel);

            return self::SUCCESS;
        }

        $this->info('Article Statistics');
        $this->line(str_repeat('─', 30));
        $this->line("Total articles:   {$total}");
        $this->line("Soft-deleted:     {$softDeleted}");

        $this->newLine();
        $this->displaySourceBreakdown($articleModel, $since);
        $this->newLine();
        $this->displayTagBreakdown($tagModel);
        $this->newLine();
        $this->displayIngestionRate($articleModel);

        return self::SUCCESS;
    }

    protected function displaySourceBreakdown(string $articleModel, $since): void
    {
        $this->info('By Source (top 10)');
        $this->line(str_repeat('─', 30));

        $sources = $articleModel::query()
            ->selectRaw('news_source_id, COUNT(*) as count')
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
            ->groupBy('news_source_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $newsSourceModel = config('northcloud.models.news_source');
        foreach ($sources as $row) {
            $source = $newsSourceModel::find($row->news_source_id);
            $name = $source ? str_pad($source->name, 25) : str_pad('Unknown', 25);
            $this->line("{$name} {$row->count}");
        }
    }

    protected function displayTagBreakdown(string $tagModel): void
    {
        $this->info('By Tag (top 10)');
        $this->line(str_repeat('─', 30));

        $tags = $tagModel::orderByDesc('article_count')->limit(10)->get();
        foreach ($tags as $tag) {
            $name = str_pad($tag->name, 25);
            $this->line("{$name} {$tag->article_count}");
        }
    }

    protected function displayIngestionRate(string $articleModel): void
    {
        $this->info('Ingestion Rate');
        $this->line(str_repeat('─', 30));

        $today = $articleModel::where('created_at', '>=', now()->startOfDay())->count();
        $week = $articleModel::where('created_at', '>=', now()->subWeek())->count();
        $month = $articleModel::where('created_at', '>=', now()->subMonth())->count();

        $this->line("Today:            {$today}");
        $this->line("This week:        {$week}");
        $this->line("This month:       {$month}");
    }

    protected function outputJson(string $articleModel, string $newsSourceModel, string $tagModel, int $total, int $softDeleted, $since): int
    {
        $data = [
            'total' => $total,
            'soft_deleted' => $softDeleted,
            'today' => $articleModel::where('created_at', '>=', now()->startOfDay())->count(),
            'this_week' => $articleModel::where('created_at', '>=', now()->subWeek())->count(),
            'this_month' => $articleModel::where('created_at', '>=', now()->subMonth())->count(),
        ];

        $this->line(json_encode($data, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }

    protected function parseSince(?string $since)
    {
        if (! $since) {
            return null;
        }

        if (preg_match('/^(\d+)h$/', $since, $m)) {
            return now()->subHours((int) $m[1]);
        }

        if (preg_match('/^(\d+)d$/', $since, $m)) {
            return now()->subDays((int) $m[1]);
        }

        return null;
    }
}
```

**Step 4: Register command in service provider**

Add `Console\Commands\ArticlesStats::class` to the `$this->commands()` array.

**Step 5: Run test to verify it passes**

Run: `vendor/bin/pest tests/Feature/ArticlesStatsCommandTest.php -v`
Expected: PASS

**Step 6: Commit**

```bash
git add src/Console/Commands/ArticlesStats.php tests/Feature/ArticlesStatsCommandTest.php src/NorthCloudServiceProvider.php
git commit -m "feat: add articles:stats command with source/tag breakdowns and JSON output"
```

---

### Task 13: `articles:test-publish` Command

**Files:**
- Create: `src/Console/Commands/ArticlesTestPublish.php`
- Test: `tests/Feature/ArticlesTestPublishCommandTest.php`

**Step 1: Write the test**

Create `tests/Feature/ArticlesTestPublishCommandTest.php`:

```php
<?php

it('registers the articles:test-publish command', function () {
    $this->artisan('list')
        ->expectsOutputToContain('articles:test-publish');
});

it('generates a valid test article payload in dry run mode', function () {
    $this->artisan('articles:test-publish --dry-run')
        ->expectsOutputToContain('test-')
        ->expectsOutputToContain('title')
        ->assertExitCode(0);
});

it('uses custom quality score', function () {
    $this->artisan('articles:test-publish --dry-run --quality=95')
        ->expectsOutputToContain('95')
        ->assertExitCode(0);
});
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/ArticlesTestPublishCommandTest.php -v`
Expected: FAIL

**Step 3: Create `src/Console/Commands/ArticlesTestPublish.php`**

```php
<?php

namespace JonesRussell\NorthCloud\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ArticlesTestPublish extends Command
{
    protected $signature = 'articles:test-publish
        {--channel= : Target Redis channel (defaults to first configured channel)}
        {--quality=75 : Quality score for the test article}
        {--dry-run : Show the payload without publishing}';

    protected $description = 'Publish a test article to verify end-to-end pipeline';

    public function handle(): int
    {
        $channel = $this->option('channel')
            ?? config('northcloud.redis.channels.0', 'articles:default');
        $quality = (int) $this->option('quality');

        $payload = $this->buildTestPayload($channel, $quality);
        $json = json_encode($payload, JSON_PRETTY_PRINT);

        if ($this->option('dry-run')) {
            $this->info('Dry run — would publish to: ' . $channel);
            $this->newLine();
            $this->line($json);

            return self::SUCCESS;
        }

        $connection = config('northcloud.redis.connection', 'northcloud');
        $redisConfig = config("database.redis.{$connection}");

        if (! $redisConfig) {
            $this->error("Redis connection [{$connection}] not configured.");

            return self::FAILURE;
        }

        try {
            $client = new \Redis;
            $host = $redisConfig['host'] ?? '127.0.0.1';
            $port = (int) ($redisConfig['port'] ?? 6379);
            $password = $redisConfig['password'] ?? null;

            $client->connect($host, $port);
            if ($password) {
                $client->auth($password);
            }

            $subscribers = $client->publish($channel, json_encode($payload));
            $client->close();

            $this->info("Published test article to [{$channel}] ({$subscribers} subscriber(s) received)");
            $this->line("External ID: {$payload['id']}");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to publish: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    protected function buildTestPayload(string $channel, int $quality): array
    {
        $id = 'test-' . Str::uuid();

        return [
            'id' => $id,
            'title' => 'Test Article: ' . fake()->sentence(6),
            'canonical_url' => 'https://test.northcloud.example/' . Str::slug(fake()->sentence(3)),
            'source' => 'https://test.northcloud.example',
            'published_date' => now()->toIso8601String(),
            'publisher' => [
                'route_id' => 'test-route',
                'published_at' => now()->toIso8601String(),
                'channel' => $channel,
            ],
            'intro' => fake()->paragraph(),
            'body' => '<p>' . fake()->paragraphs(2, true) . '</p>',
            'topics' => ['test'],
            'quality_score' => $quality,
        ];
    }
}
```

**Step 4: Register command in service provider**

Add `Console\Commands\ArticlesTestPublish::class` to the `$this->commands()` array.

**Step 5: Run test to verify it passes**

Run: `vendor/bin/pest tests/Feature/ArticlesTestPublishCommandTest.php -v`
Expected: PASS

**Step 6: Commit**

```bash
git add src/Console/Commands/ArticlesTestPublish.php tests/Feature/ArticlesTestPublishCommandTest.php src/NorthCloudServiceProvider.php
git commit -m "feat: add articles:test-publish command for pipeline verification"
```

---

### Task 14: `articles:replay` Command

**Files:**
- Create: `src/Console/Commands/ArticlesReplay.php`
- Test: `tests/Feature/ArticlesReplayCommandTest.php`

**Step 1: Write the test**

Create `tests/Feature/ArticlesReplayCommandTest.php`:

```php
<?php

use JonesRussell\NorthCloud\Models\Article;

it('registers the articles:replay command', function () {
    $this->artisan('list')
        ->expectsOutputToContain('articles:replay');
});

it('replays a specific article by ID', function () {
    $article = Article::factory()->create([
        'metadata' => ['quality_score' => 80, 'publisher' => ['channel' => 'test']],
    ]);

    $this->artisan("articles:replay --id={$article->id} --dry-run")
        ->expectsOutputToContain($article->title)
        ->assertExitCode(0);
});

it('replays articles from a time range', function () {
    Article::factory()->count(3)->create(['created_at' => now()->subHours(2)]);
    Article::factory()->count(2)->create(['created_at' => now()->subDays(3)]);

    $this->artisan('articles:replay --since=24h --dry-run')
        ->expectsOutputToContain('3 article(s)')
        ->assertExitCode(0);
});
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/ArticlesReplayCommandTest.php -v`
Expected: FAIL

**Step 3: Create `src/Console/Commands/ArticlesReplay.php`**

```php
<?php

namespace JonesRussell\NorthCloud\Console\Commands;

use Illuminate\Console\Command;
use JonesRussell\NorthCloud\Processing\ProcessorPipeline;

class ArticlesReplay extends Command
{
    protected $signature = 'articles:replay
        {--id= : Replay a specific article by ID}
        {--since= : Replay articles from the last N hours/days (e.g., 24h, 7d)}
        {--dry-run : Show which articles would be replayed without processing}';

    protected $description = 'Re-process existing articles through the processor pipeline';

    public function handle(ProcessorPipeline $pipeline): int
    {
        $articleModel = config('northcloud.models.article');
        $query = $articleModel::query();

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        } elseif ($since = $this->option('since')) {
            $from = $this->parseSince($since);
            if ($from) {
                $query->where('created_at', '>=', $from);
            }
        } else {
            $this->error('Specify --id or --since to select articles.');

            return self::FAILURE;
        }

        $articles = $query->get();

        if ($this->option('dry-run')) {
            $this->info("{$articles->count()} article(s) would be replayed:");
            foreach ($articles as $article) {
                $this->line("  [{$article->id}] {$article->title}");
            }

            return self::SUCCESS;
        }

        $this->info("Replaying {$articles->count()} article(s)...");
        $processed = 0;
        $errors = 0;

        foreach ($articles as $article) {
            try {
                $data = $this->reconstructData($article);
                $pipeline->run($data);
                $processed++;

                if ($this->option('verbose')) {
                    $this->line("  Replayed: {$article->title}");
                }
            } catch (\Exception $e) {
                $errors++;
                $this->error("  Failed [{$article->id}]: {$e->getMessage()}");
            }
        }

        $this->info("Done. Processed: {$processed}, Errors: {$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    protected function reconstructData($article): array
    {
        $metadata = $article->metadata ?? [];

        return [
            'id' => $article->external_id ?? "replay-{$article->id}",
            'title' => $article->title,
            'canonical_url' => $article->url,
            'intro' => $article->excerpt,
            'body' => $article->content,
            'author' => $article->author,
            'published_date' => $article->published_at?->toIso8601String(),
            'quality_score' => $metadata['quality_score'] ?? null,
            'publisher' => $metadata['publisher'] ?? [],
            'crime_relevance' => $metadata['crime_relevance'] ?? null,
            'mining' => $metadata['mining'] ?? null,
            'image_url' => $article->image_url,
            'topics' => $article->tags->pluck('slug')->all(),
        ];
    }

    protected function parseSince(?string $since)
    {
        if (! $since) {
            return null;
        }

        if (preg_match('/^(\d+)h$/', $since, $m)) {
            return now()->subHours((int) $m[1]);
        }

        if (preg_match('/^(\d+)d$/', $since, $m)) {
            return now()->subDays((int) $m[1]);
        }

        return null;
    }
}
```

**Step 4: Register command in service provider**

Add `Console\Commands\ArticlesReplay::class` to the `$this->commands()` array.

**Step 5: Run test to verify it passes**

Run: `vendor/bin/pest tests/Feature/ArticlesReplayCommandTest.php -v`
Expected: PASS

**Step 6: Commit**

```bash
git add src/Console/Commands/ArticlesReplay.php tests/Feature/ArticlesReplayCommandTest.php src/NorthCloudServiceProvider.php
git commit -m "feat: add articles:replay command for re-processing existing articles"
```

---

### Task 15: Finalize Service Provider

**Files:**
- Modify: `src/NorthCloudServiceProvider.php`
- Test: `tests/Feature/ServiceProviderTest.php`

**Step 1: Write the test**

Create `tests/Feature/ServiceProviderTest.php`:

```php
<?php

it('registers all artisan commands', function () {
    $commands = ['articles:subscribe', 'articles:status', 'articles:stats', 'articles:test-publish', 'articles:replay'];

    foreach ($commands as $command) {
        $this->artisan('list')
            ->expectsOutputToContain($command);
    }
});

it('merges default config', function () {
    expect(config('northcloud'))->toBeArray();
    expect(config('northcloud.redis.connection'))->toBe('northcloud');
});

it('registers ArticleIngestionService as singleton', function () {
    $a = app(\JonesRussell\NorthCloud\Services\ArticleIngestionService::class);
    $b = app(\JonesRussell\NorthCloud\Services\ArticleIngestionService::class);

    expect($a)->toBe($b);
});
```

**Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/ServiceProviderTest.php -v`
Expected: Partial fail — singleton binding not yet registered.

**Step 3: Write final `src/NorthCloudServiceProvider.php`**

```php
<?php

namespace JonesRussell\NorthCloud;

use Illuminate\Support\ServiceProvider;
use JonesRussell\NorthCloud\Services\ArticleIngestionService;
use JonesRussell\NorthCloud\Services\NewsSourceResolver;

class NorthCloudServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/northcloud.php', 'northcloud');

        $this->app->singleton(NewsSourceResolver::class);
        $this->app->singleton(ArticleIngestionService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\SubscribeToArticleFeed::class,
                Console\Commands\ArticlesStatus::class,
                Console\Commands\ArticlesStats::class,
                Console\Commands\ArticlesTestPublish::class,
                Console\Commands\ArticlesReplay::class,
            ]);

            $this->publishes([
                __DIR__ . '/../config/northcloud.php' => config_path('northcloud.php'),
            ], 'northcloud-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'northcloud-migrations');
        }
    }
}
```

**Step 4: Run test to verify it passes**

Run: `vendor/bin/pest tests/Feature/ServiceProviderTest.php -v`
Expected: PASS

**Step 5: Run the full test suite**

Run: `vendor/bin/pest -v`
Expected: All tests PASS

**Step 6: Commit**

```bash
git add src/NorthCloudServiceProvider.php tests/Feature/ServiceProviderTest.php
git commit -m "feat: finalize service provider with all commands and singleton bindings"
```

---

### Task 16: Code Quality and Formatting

**Files:**
- Create: `pint.json`

**Step 1: Create `pint.json`**

```json
{
    "preset": "laravel"
}
```

**Step 2: Run Pint to format all PHP files**

Run: `vendor/bin/pint`
Expected: All files formatted to Laravel style.

**Step 3: Run the full test suite one final time**

Run: `vendor/bin/pest -v`
Expected: All tests PASS

**Step 4: Commit**

```bash
git add -A
git commit -m "style: apply Laravel Pint formatting"
```

---

### Task 17: Tag v0.1.0 Release

**Step 1: Create a `README.md`** (minimal, just for the repo)

```markdown
# NorthCloud Laravel

Shared article ingestion pipeline for North Cloud-connected Laravel sites.

## Installation

```bash
composer require jonesrussell/northcloud-laravel
```

## Configuration

```bash
php artisan vendor:publish --tag=northcloud-config
```

## Commands

| Command | Description |
|---------|-------------|
| `articles:subscribe` | Subscribe to Redis pub/sub for incoming articles |
| `articles:status` | Show connection health and recent activity |
| `articles:stats` | Display aggregate article statistics |
| `articles:test-publish` | Publish a test article to verify pipeline |
| `articles:replay` | Re-process existing articles through pipeline |

## License

MIT
```

**Step 2: Push to GitHub**

```bash
git remote add origin git@github.com:jonesrussell/northcloud-laravel.git
git push -u origin main
```

**Step 3: Tag the release**

```bash
git tag v0.1.0
git push origin v0.1.0
```

---

## Summary

| Task | What It Builds | Tests |
|------|---------------|-------|
| 1 | Repo scaffold, Pest, TestCase | — |
| 2 | Config, Contracts | ConfigTest |
| 3 | 4 Migrations | MigrationTest |
| 4 | Article, NewsSource, Tag models + factories | 3 model test files |
| 5 | NewsSourceResolver | NewsSourceResolverTest |
| 6 | ArticleIngestionService | ArticleIngestionServiceTest |
| 7 | DefaultArticleProcessor, ProcessorPipeline | ProcessorPipelineTest |
| 8 | ArticleReceived, ArticleProcessed events | EventTest |
| 9 | ProcessIncomingArticle job | ProcessIncomingArticleJobTest |
| 10 | `articles:subscribe` command | SubscribeCommandTest |
| 11 | `articles:status` command | ArticlesStatusCommandTest |
| 12 | `articles:stats` command | ArticlesStatsCommandTest |
| 13 | `articles:test-publish` command | ArticlesTestPublishCommandTest |
| 14 | `articles:replay` command | ArticlesReplayCommandTest |
| 15 | Finalized ServiceProvider | ServiceProviderTest |
| 16 | Pint formatting | — |
| 17 | README + v0.1.0 tag | — |
