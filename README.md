# StreetCode Laravel

A modern news aggregation platform built with Laravel 12, Inertia.js, and Vue 3. StreetCode aggregates crime-related news articles from multiple sources via Redis pub/sub, processes and categorizes them, and presents them in a clean, searchable interface.

## Features

### Core Functionality
- **Article Aggregation**: Real-time ingestion of articles via Redis pub/sub
- **Article Management**: Automatic processing, deduplication, and categorization
- **Search & Filtering**: Full-text search with tag and source filtering
- **News Sources**: Automatic source detection and credibility tracking
- **Tagging System**: Automatic categorization with confidence scoring
- **Featured Articles**: Highlight important articles
- **View Tracking**: Track article popularity

### User Features
- **User Authentication**: Registration, login, password reset via Laravel Fortify
- **Email Verification**: Secure email verification for new accounts
- **Two-Factor Authentication**: TOTP-based 2FA with QR codes and recovery codes
- **User Dashboard**: Personalized dashboard for authenticated users
- **Settings Management**: Profile, password, appearance, and 2FA settings
- **Dark Mode Support**: Theme switching with persistent preferences

### Technical Features
- **Queue Processing**: Asynchronous article processing with Laravel Horizon
- **Quality Filtering**: Configurable quality score thresholds
- **Soft Deletes**: Recoverable article deletion
- **Full-Text Search**: MySQL full-text search capabilities
- **Type Safety**: TypeScript support with Wayfinder for route/controller types
- **SSR Support**: Server-side rendering support with Inertia.js

## Tech Stack

### Backend
- **PHP**: 8.4.10
- **Laravel**: 12.x
- **Laravel Fortify**: 1.x (Authentication)
- **Laravel Horizon**: 5.x (Queue Management)
- **Laravel Wayfinder**: 0.x (Type-safe routes)
- **Laravel Pint**: 1.x (Code Formatting)

### Frontend
- **Vue**: 3.5.13
- **Inertia.js**: 2.x (SPA Framework)
- **Tailwind CSS**: 4.x (Styling)
- **TypeScript**: 5.2.2
- **Vite**: 7.x (Build Tool)

### Testing
- **Pest**: 4.x (Testing Framework)
- **PHPUnit**: 12.x

### Infrastructure
- **Redis**: Pub/sub for article ingestion and queue management
- **MariaDB**: 10.11 (Database)
- **Nginx**: Web server (production)
- **Docker**: Containerization
- **Let's Encrypt**: SSL certificates (production)

## Requirements

- PHP 8.2 or higher
- Composer
- Node.js 18+ and npm
- Redis (for queue and pub/sub)
- MariaDB/MySQL 10.11+
- Docker & Docker Compose (for production deployment)

## Installation

### 1. Clone the repository

```bash
git clone <repository-url>
cd streetcode-laravel
```

### 2. Install dependencies

```bash
composer install
npm install
```

### 3. Environment setup

```bash
cp .env.example .env
php artisan key:generate
```

Configure your `.env` file with:
- Database credentials
- Redis connection details
- Mail settings (for email verification)
- Application URL

### 4. Database setup

```bash
php artisan migrate
```

### 5. Build assets

```bash
npm run build
```

Or for development with hot reloading:

```bash
npm run dev
```

## Development

### Running the application

The project includes a convenient development script that runs all services concurrently:

```bash
composer run dev
```

This starts:
- Laravel development server
- Laravel Horizon (queue worker)
- Laravel Pail (log viewer)
- Vite dev server (frontend hot reloading)

### Running individual services

```bash
# PHP development server
php artisan serve

# Queue worker (Horizon)
php artisan horizon

# Frontend dev server
npm run dev

# Log viewer
php artisan pail
```

### Code formatting

```bash
# Format PHP code
vendor/bin/pint --dirty

# Format JavaScript/Vue code
npm run format

# Lint JavaScript/Vue code
npm run lint
```

### Testing

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/ArticleTest.php

# Run tests with filter
php artisan test --filter=ArticleController
```

### DDEV Development

This project is configured to work with [DDEV](https://ddev.readthedocs.io/) for local development. DDEV automatically starts the `articles:subscribe` command when you start the project.

```bash
# Start DDEV (articles:subscribe starts automatically)
ddev start

# Check if articles:subscribe is running
ddev exec ps aux | grep articles:subscribe

# View logs
ddev exec tail -f storage/logs/laravel.log

# Restart the articles:subscribe daemon
ddev restart
```

**Note**: DDEV's `nginx-fpm` webserver type already uses supervisor internally, so the `articles:subscribe` command runs directly as a daemon rather than through a separate supervisor instance. For production Docker deployment, supervisor is used as documented in the Production Deployment section.

## Article Ingestion

Articles are ingested via Redis pub/sub. The application subscribes to a Redis channel and processes incoming articles asynchronously.

### Starting the article subscriber

```bash
php artisan articles:subscribe
```

By default, it subscribes to the `articles:crime` channel. You can specify a different channel:

```bash
php artisan articles:subscribe articles:news
```

### Article Quality Filtering

Set a minimum quality score threshold in your `.env`:

```env
ARTICLES_MIN_QUALITY_SCORE=70
```

Articles with a quality score below this threshold will be filtered out.

### Article Processing

When an article is received via Redis:
1. Message is validated against publisher format
2. Quality score is checked (if configured)
3. `ProcessIncomingArticle` job is dispatched to the queue
4. Job processes the article:
   - Deduplication check (by `external_id`)
   - News source creation/lookup
   - Article creation with metadata
   - Tag attachment from topics array

### Publisher Message Format

Articles must follow this format:

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
  "body": "Article content (HTML allowed)",
  "topics": ["crime", "theft"],
  "quality_score": 85,
  "source_reputation": 90
}
```

## Production Deployment

### Docker Deployment

The project includes Docker configuration for production deployment. See `docker/README.md` for detailed setup instructions.

### Key Production Considerations

1. **Queue Workers**: Ensure Horizon is running to process articles
2. **Article Subscriber**: Run `php artisan articles:subscribe` as a supervisor-managed process
3. **SSL**: Configure Let's Encrypt certificates via Certbot
4. **Redis**: Use external Redis service for production
5. **Database**: Use persistent volumes for MariaDB data
6. **Environment**: Set `APP_ENV=production` and `APP_DEBUG=false`

### Supervisor Configuration

For production, configure Supervisor to manage:
- `php artisan articles:subscribe` (Redis subscriber)
- `php artisan horizon` (Queue worker)

Example Supervisor config:

```ini
[program:streetcode-subscriber]
command=php /var/www/html/artisan articles:subscribe
directory=/var/www/html
autostart=true
autorestart=true
user=www-data
```

## Project Structure

```
app/
├── Actions/          # Fortify authentication actions
├── Console/          # Artisan commands
│   └── Commands/
│       └── SubscribeToArticleFeed.php
├── Http/
│   ├── Controllers/  # Application controllers
│   ├── Middleware/   # Custom middleware
│   └── Requests/     # Form request validation
├── Jobs/             # Queue jobs
│   ├── ProcessIncomingArticle.php
│   └── UpdateTagArticleCounts.php
├── Models/           # Eloquent models
│   ├── Article.php
│   ├── NewsSource.php
│   ├── Tag.php
│   └── User.php
└── Providers/        # Service providers

resources/
└── js/
    ├── components/   # Vue components
    ├── layouts/      # Inertia layouts
    ├── pages/        # Inertia pages
    ├── routes/       # Wayfinder-generated routes
    └── app.ts        # Application entry point

routes/
├── web.php           # Web routes
└── settings.php      # Settings routes

tests/
├── Feature/          # Feature tests
└── Unit/             # Unit tests
```

## Database Schema

### Articles
- Articles with content, metadata, and relationships
- Soft deletes enabled
- Full-text search on title, excerpt, and content

### News Sources
- Source information with credibility scores
- Automatic creation from article URLs

### Tags
- Categorization system with types
- Article count tracking
- Many-to-many relationship with articles

### Users
- Standard user authentication
- Two-factor authentication support
- Email verification

## Authentication

The application uses Laravel Fortify for authentication with the following features enabled:

- **Registration**: User registration
- **Password Reset**: Email-based password reset
- **Email Verification**: Required for new accounts
- **Two-Factor Authentication**: TOTP with QR codes

Settings pages are available at:
- `/settings/profile` - Update profile information
- `/settings/password` - Change password
- `/settings/appearance` - Theme preferences
- `/settings/two-factor` - 2FA management

## Queue Management

Laravel Horizon is used for queue management. Access the Horizon dashboard at `/horizon` (requires authentication in production).

Monitor queue performance, failed jobs, and worker status through the dashboard.

## Contributing

1. Follow existing code conventions
2. Write tests for new features
3. Run `vendor/bin/pint --dirty` before committing
4. Ensure all tests pass: `php artisan test`

## License

MIT License

