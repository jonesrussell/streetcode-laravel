# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

StreetCode is a news aggregation platform that ingests crime-related articles via Redis pub/sub, processes them asynchronously, and presents them in a searchable interface.

## Development Commands

```bash
# Start all development services (server, horizon, pail, vite)
composer run dev

# Run tests
php artisan test --compact                              # all tests
php artisan test --compact --filter=TestName            # specific test
php artisan test --compact tests/Feature/ArticleTest.php  # specific file

# Format code
vendor/bin/pint --dirty      # PHP (before committing)
npm run format               # Vue/JS
npm run lint                 # ESLint

# Type checking
npm run type-check           # TypeScript

# Build frontend
npm run build                # production
npm run dev                  # development with HMR
```

## Architecture

### Article Processing Pipeline

Uses `jonesrussell/northcloud-laravel` package for shared ingestion infrastructure:

1. **Ingestion**: `php artisan articles:subscribe` (package command) subscribes to Redis crime channels configured in `config/northcloud.php`
2. **Validation**: Messages validated by package, quality score checked if enabled
3. **Pipeline**: `ProcessIncomingArticle` job dispatches to `ProcessorPipeline`
4. **Processing**: `CrimeArticleProcessor` gates on `crime_relevance === 'core_street_crime'`, delegates to `ArticleIngestionService` for dedup/source/tags, then adds city linking, extended metadata, and crime tag filtering

### Key Model Relationships

- `Article` → belongsTo `NewsSource`, belongsTo `City`, belongsTo `User` (author), belongsToMany `Tag` (with confidence pivot)
- `Tag` → belongsToMany `Article`
- `NewsSource` → hasMany `Article`
- `City` → hasMany `Article`

Models extend package base classes (`JonesRussell\NorthCloud\Models\*`) and add streetcode-specific features.

### Frontend Architecture

- **Stack**: Vue 3 + Inertia.js v2 + TypeScript + Tailwind CSS v4
- **Pages**: `resources/js/pages/` (auto-routed by Inertia)
- **Layouts**: `resources/js/layouts/` (AppLayout, AuthLayout)
- **Components**: `resources/js/components/` (shadcn/reka-ui based)
- **Type-safe routes**: Wayfinder generates `@/actions/` and `@/routes/`

### Database Features

- Full-text search indexes on articles (title, excerpt, content) - MySQL only
- Soft deletes on articles
- JSON metadata column for publisher data, quality scores, crime classification
- `slug` and `status` columns on articles (auto-generated slug on create)

## Key Files

| File | Purpose |
|------|---------|
| `bootstrap/app.php` | Middleware, exceptions, routing config |
| `routes/web.php` | Public and dashboard routes |
| `routes/admin.php` | Admin-protected routes |
| `config/northcloud.php` | Package config: channels, models, processors |
| `app/Processing/CrimeArticleProcessor.php` | Crime filtering, city linking, metadata |
| `app/Models/Article.php` | Extends package Article with city/search/slug |
| `app/Http/Middleware/HandleInertiaRequests.php` | Shared props for all pages |

## Article Message Format

Articles received via Redis must follow this structure:

```json
{
  "id": "unique-external-id",
  "title": "Article Title",
  "canonical_url": "https://example.com/article",
  "source": "https://example.com",
  "published_date": "2024-01-01T00:00:00Z",
  "publisher": {
    "route_id": "route-123",
    "published_at": "2024-01-01T00:00:00Z",
    "channel": "articles:crime"
  },
  "intro": "Article excerpt",
  "body": "Article content",
  "topics": ["crime", "theft"],
  "quality_score": 85,
  "crime_relevance": "core_street_crime"
}
```

## Testing Conventions

- Use Pest 4 for all tests
- Feature tests in `tests/Feature/`, unit tests in `tests/Unit/`
- Use model factories with states for test data
- Browser tests in `tests/Browser/` when needed

## Production

- Deployed via Deployer from GitHub Actions (triggers after tests pass on main)
- Deploy config: `deploy.php` → deploys to `streetcode.net` as `deployer` user
- systemd user services in `~deployer/.config/systemd/user/` manage Horizon, SSR, and article subscriber
- Horizon dashboard at `/horizon` (authenticated)
- Post-deploy: terminates Horizon and SSR for graceful restart
- DDEV for local development (auto-starts article subscriber via supervisor)
