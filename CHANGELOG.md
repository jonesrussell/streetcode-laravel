# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2026-03-17

### Added

- Article ingestion pipeline via NorthCloud Redis pub/sub with quality score filtering
- Article listing, detail, and preview pages with rich metadata
- Slug-based URLs for articles with SEO-friendly routing
- Dedicated tag pages with proper routing
- Country pages that display articles immediately on load
- Article preview page with Open Graph and structured metadata
- Admin dashboard with article management (CRUD, bulk actions, soft-delete)
- Trashed articles management page for reviewing soft-deleted content
- Command to soft-delete articles by non-crime title patterns
- Crime-only channel subscription filtering (Layer 1 topic channels)
- Ingestion metrics dashboard (InstrumentedCrimeArticleProcessor, IngestionMetricsService)
- ArticleImage component replacing raw img tags across the frontend
- Sidebar navigation with dynamic admin links
- NorthCloud Laravel package integration (v0.3) for user management and shared composables
- Alloy configuration and systemd service for log ingestion to Loki
- Deployment pipeline with Caddy, systemd user services, and PHP-FPM restart
- Cloudflared and ngrok share providers for DDEV local development
- SSR support with configurable port via environment variable
- CI workflow with automated linting and testing

### Changed

- NorthCloud channel definitions updated for clarity and consistency
- Caddy configuration aligned with standard pattern (TLS, encode, static handles, logging)
- Migrated admin article management to northcloud-laravel package
- Improved caching and accessibility for static assets and images
- Enhanced type safety across Vue components
- Cleaned up component code and removed unused imports

### Fixed

- Duplicate og:type and og:image meta tags on article pages
- External article links missing rel="noopener noreferrer"
- SSR port conflicts between applications (now configurable via INERTIA_SSR_PORT)
- NorthCloud Redis connection not registered for article subscriber
- Slug migration made idempotent for partial deploy recovery
- Slugs truncated to 255 characters during migration backfill
- Soft-delete patterns refined to avoid false positives and preserve articles:crime channel
- MariaDB compatibility in soft-delete command
- Flaky test from non-unique company names in NewsSourceFactory
- Alloy-loki service removed from systemd enable/restart (runs in Docker)
- Deploy tolerates missing alloy-loki service gracefully
- PHP-FPM restarted after deploy to clear realpath cache
- HeroBriefing component layout styles corrected
