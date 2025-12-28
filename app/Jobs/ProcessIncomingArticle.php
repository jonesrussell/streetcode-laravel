<?php

namespace App\Jobs;

use App\Models\Article;
use App\Models\NewsSource;
use App\Models\Tag;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessIncomingArticle implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $articleData
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Validate required fields from publisher format
            if (! $this->validatePublisherMessage()) {
                Log::warning('Invalid article data: missing required fields', [
                    'data_keys' => array_keys($this->articleData),
                ]);

                return;
            }

            $externalId = $this->articleData['id'];

            // Deduplication check
            if (Article::where('external_id', $externalId)->exists()) {
                Log::debug('Skipping duplicate article', [
                    'external_id' => $externalId,
                ]);

                return;
            }

            // Get or create news source from URL
            $sourceId = $this->getOrCreateSource($this->articleData['source']);

            // Build metadata from publisher fields
            $metadata = $this->buildMetadata();

            // Map publisher fields to article fields
            $article = Article::create([
                'news_source_id' => $sourceId,
                'external_id' => $externalId,
                'title' => $this->articleData['title'],
                'excerpt' => $this->articleData['intro'] ?? $this->articleData['description'] ?? null,
                'content' => $this->sanitizeContent($this->articleData['body'] ?? $this->articleData['raw_text'] ?? null),
                'url' => $this->articleData['canonical_url'],
                'image_url' => $this->articleData['og_image'] ?? null,
                'author' => $this->articleData['author'] ?? null,
                'published_at' => Carbon::parse($this->articleData['published_date']),
                'crawled_at' => now(),
                'metadata' => $metadata,
            ]);

            // Attach tags from topics array
            if (! empty($this->articleData['topics'])) {
                $this->attachTags($article, $this->articleData['topics']);
            }

            Log::info('Article processed successfully', [
                'article_id' => $article->id,
                'external_id' => $article->external_id,
                'title' => $article->title,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process incoming article', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'external_id' => $this->articleData['id'] ?? null,
            ]);

            throw $e;
        }
    }

    /**
     * Validate that the message follows the publisher service format.
     */
    protected function validatePublisherMessage(): bool
    {
        $requiredFields = ['id', 'title', 'canonical_url', 'source', 'published_date'];

        foreach ($requiredFields as $field) {
            if (! isset($this->articleData[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get or create NewsSource from URL string.
     */
    protected function getOrCreateSource(string $sourceUrl): int
    {
        try {
            $parsedUrl = parse_url($sourceUrl);

            if (! isset($parsedUrl['host'])) {
                Log::warning('Unable to parse source URL', ['url' => $sourceUrl]);

                return $this->getOrCreateUnknownSource($sourceUrl);
            }

            $domain = $parsedUrl['host'];
            $name = $this->formatDomainName($domain);
            $slug = Str::slug($name);

            $source = NewsSource::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'url' => $sourceUrl,
                    'is_active' => true,
                    'credibility_score' => $this->articleData['source_reputation'] ?? null,
                ]
            );

            // Update source_reputation if provided and source already exists
            if (! $source->wasRecentlyCreated && isset($this->articleData['source_reputation'])) {
                $source->update(['credibility_score' => $this->articleData['source_reputation']]);
            }

            return $source->id;
        } catch (\Exception $e) {
            Log::error('Failed to create news source', [
                'url' => $sourceUrl,
                'error' => $e->getMessage(),
            ]);

            return $this->getOrCreateUnknownSource($sourceUrl);
        }
    }

    /**
     * Format domain name for display (e.g., "example.com" -> "Example").
     */
    protected function formatDomainName(string $domain): string
    {
        // Remove www. prefix
        $domain = preg_replace('/^www\./', '', $domain);

        // Extract main domain name (e.g., "example.com" -> "example")
        $parts = explode('.', $domain);
        $name = $parts[0] ?? $domain;

        return Str::title($name);
    }

    /**
     * Get or create unknown source fallback.
     */
    protected function getOrCreateUnknownSource(string $url): int
    {
        $source = NewsSource::firstOrCreate(
            ['slug' => 'unknown'],
            [
                'name' => 'Unknown Source',
                'url' => $url,
                'is_active' => true,
            ]
        );

        return $source->id;
    }

    /**
     * Build metadata array from publisher fields.
     */
    protected function buildMetadata(): array
    {
        $metadata = [];

        // Publisher metadata
        if (isset($this->articleData['publisher']) && is_array($this->articleData['publisher'])) {
            $metadata['publisher'] = [
                'route_id' => $this->articleData['publisher']['route_id'] ?? null,
                'published_at' => $this->articleData['publisher']['published_at'] ?? null,
                'channel' => $this->articleData['publisher']['channel'] ?? null,
            ];
        }

        // Classification metadata
        if (isset($this->articleData['quality_score'])) {
            $metadata['quality_score'] = $this->articleData['quality_score'];
        }

        if (isset($this->articleData['source_reputation'])) {
            $metadata['source_reputation'] = $this->articleData['source_reputation'];
        }

        if (isset($this->articleData['confidence'])) {
            $metadata['confidence'] = $this->articleData['confidence'];
        }

        if (isset($this->articleData['is_crime_related'])) {
            $metadata['is_crime_related'] = $this->articleData['is_crime_related'];
        }

        if (isset($this->articleData['content_type'])) {
            $metadata['content_type'] = $this->articleData['content_type'];
        }

        // Additional fields
        if (isset($this->articleData['word_count'])) {
            $metadata['word_count'] = $this->articleData['word_count'];
        }

        if (isset($this->articleData['category'])) {
            $metadata['category'] = $this->articleData['category'];
        }

        if (isset($this->articleData['section'])) {
            $metadata['section'] = $this->articleData['section'];
        }

        if (isset($this->articleData['keywords']) && is_array($this->articleData['keywords'])) {
            $metadata['keywords'] = $this->articleData['keywords'];
        }

        // Open Graph metadata
        if (isset($this->articleData['og_title'])) {
            $metadata['og_title'] = $this->articleData['og_title'];
        }

        if (isset($this->articleData['og_description'])) {
            $metadata['og_description'] = $this->articleData['og_description'];
        }

        if (isset($this->articleData['og_url'])) {
            $metadata['og_url'] = $this->articleData['og_url'];
        }

        return $metadata;
    }

    /**
     * Sanitize HTML content while preserving basic formatting.
     */
    protected function sanitizeContent(?string $content): ?string
    {
        if (! $content) {
            return null;
        }

        // Strip dangerous tags but preserve basic formatting
        return strip_tags($content, '<p><br><a><strong><em><ul><ol><li><h1><h2><h3><h4><h5><h6>');
    }

    /**
     * Attach tags to article from topics array.
     */
    protected function attachTags(Article $article, array $topics): void
    {
        $tagIds = [];

        foreach ($topics as $topic) {
            // Convert topic to slug format
            $tagSlug = Str::slug($topic);

            // Find or create tag
            $tag = Tag::firstOrCreate(
                ['slug' => $tagSlug],
                [
                    'name' => Str::title(str_replace('-', ' ', $tagSlug)),
                    'type' => 'crime_category',
                ]
            );

            $tagIds[] = $tag->id;
        }

        // Sync tags to article
        if (! empty($tagIds)) {
            $article->tags()->sync($tagIds);
        }
    }
}
