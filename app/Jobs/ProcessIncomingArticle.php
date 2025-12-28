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
            // Validate required fields
            if (! isset($this->articleData['title']) || ! isset($this->articleData['url'])) {
                Log::warning('Invalid article data: missing required fields', $this->articleData);

                return;
            }

            // Get or create news source
            $sourceId = $this->getOrCreateSource($this->articleData['source'] ?? []);

            // Create or update article
            $article = Article::updateOrCreate(
                ['external_id' => $this->articleData['id'] ?? null],
                [
                    'news_source_id' => $sourceId,
                    'title' => $this->articleData['title'],
                    'excerpt' => $this->articleData['excerpt'] ?? null,
                    'content' => $this->sanitizeContent($this->articleData['content'] ?? null),
                    'url' => $this->articleData['url'],
                    'image_url' => $this->articleData['image'] ?? null,
                    'author' => $this->articleData['author'] ?? null,
                    'published_at' => isset($this->articleData['published_at'])
                        ? Carbon::parse($this->articleData['published_at'])
                        : now(),
                    'crawled_at' => now(),
                    'metadata' => $this->articleData['metadata'] ?? null,
                ]
            );

            // Attach tags if provided
            if (! empty($this->articleData['tags'])) {
                $this->attachTags($article, $this->articleData['tags']);
            }

            Log::info('Article processed successfully', [
                'article_id' => $article->id,
                'external_id' => $article->external_id,
                'title' => $article->title,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to process incoming article', [
                'error' => $e->getMessage(),
                'data' => $this->articleData,
            ]);

            throw $e;
        }
    }

    protected function getOrCreateSource(array $sourceData): int
    {
        if (empty($sourceData['name'])) {
            // Create a default source if none provided
            $source = NewsSource::firstOrCreate(
                ['slug' => 'unknown'],
                [
                    'name' => 'Unknown Source',
                    'url' => $sourceData['url'] ?? 'https://unknown.com',
                    'is_active' => true,
                ]
            );

            return $source->id;
        }

        $slug = Str::slug($sourceData['name']);

        $source = NewsSource::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $sourceData['name'],
                'url' => $sourceData['url'] ?? '',
                'is_active' => true,
            ]
        );

        return $source->id;
    }

    protected function sanitizeContent(?string $content): ?string
    {
        if (! $content) {
            return null;
        }

        // Strip dangerous tags but preserve basic formatting
        return strip_tags($content, '<p><br><a><strong><em><ul><ol><li><h1><h2><h3><h4><h5><h6>');
    }

    protected function attachTags(Article $article, array $tagSlugs): void
    {
        $tagIds = [];

        foreach ($tagSlugs as $tagSlug) {
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
        $article->tags()->sync($tagIds);
    }
}
