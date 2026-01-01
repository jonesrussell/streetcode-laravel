<?php

use App\Jobs\ProcessIncomingArticle;
use App\Models\Article;

it('processes a valid publisher message format', function () {
    $articleData = [
        'publisher' => [
            'route_id' => 'a1b2c3d4-e5f6-4789-a0b1-c2d3e4f5g6h7',
            'published_at' => '2025-12-28T15:30:45Z',
            'channel' => 'articles:crime',
        ],
        'id' => 'es-doc-id-12345',
        'title' => 'Local Police Investigate Break-In',
        'body' => 'Full article text content here...',
        'canonical_url' => 'https://example.com/articles/police-investigate',
        'source' => 'https://example.com/original-article-url',
        'published_date' => '2025-12-28T08:00:00Z',
        'quality_score' => 85,
        'topics' => ['crime', 'local'],
        'is_crime_related' => true,
        'source_reputation' => 78,
        'confidence' => 0.92,
        'intro' => 'Article introduction paragraph',
        'og_image' => 'https://example.com/images/article-image.jpg',
    ];

    $job = new ProcessIncomingArticle($articleData);
    $job->handle();

    $article = Article::where('external_id', 'es-doc-id-12345')->first();

    expect($article)->not->toBeNull();
    expect($article->title)->toBe('Local Police Investigate Break-In');
    expect($article->url)->toBe('https://example.com/articles/police-investigate');
    expect($article->content)->toBe('Full article text content here...');
    expect($article->excerpt)->toBe('Article introduction paragraph');
    expect($article->image_url)->toBe('https://example.com/images/article-image.jpg');
    expect($article->published_at->toIso8601String())->toBe('2025-12-28T08:00:00+00:00');
});

it('maps raw_text to content when body is not present', function () {
    $articleData = [
        'publisher' => [
            'route_id' => 'test-route',
            'published_at' => '2025-12-28T15:30:45Z',
            'channel' => 'articles:crime',
        ],
        'id' => 'es-doc-id-raw-text',
        'title' => 'Test Article',
        'raw_text' => 'Raw text content without body field',
        'canonical_url' => 'https://example.com/test',
        'source' => 'https://example.com',
        'published_date' => '2025-12-28T08:00:00Z',
    ];

    $job = new ProcessIncomingArticle($articleData);
    $job->handle();

    $article = Article::where('external_id', 'es-doc-id-raw-text')->first();

    expect($article)->not->toBeNull();
    expect($article->content)->toBe('Raw text content without body field');
});

it('creates news source from URL domain', function () {
    $articleData = [
        'publisher' => [
            'route_id' => 'test-route',
            'published_at' => '2025-12-28T15:30:45Z',
            'channel' => 'articles:crime',
        ],
        'id' => 'es-doc-id-source-test',
        'title' => 'Test Article',
        'body' => 'Content',
        'canonical_url' => 'https://example.com/test',
        'source' => 'https://news.example.com/article',
        'published_date' => '2025-12-28T08:00:00Z',
    ];

    $job = new ProcessIncomingArticle($articleData);
    $job->handle();

    $article = Article::where('external_id', 'es-doc-id-source-test')->first();
    $source = $article->newsSource;

    expect($source)->not->toBeNull();
    expect($source->url)->toBe('https://news.example.com/article');
    expect($source->name)->toBe('News');
    expect($source->slug)->toBe('news');
});

it('handles www prefix in source URL', function () {
    $articleData = [
        'publisher' => [
            'route_id' => 'test-route',
            'published_at' => '2025-12-28T15:30:45Z',
            'channel' => 'articles:crime',
        ],
        'id' => 'es-doc-id-www-test',
        'title' => 'Test Article',
        'body' => 'Content',
        'canonical_url' => 'https://example.com/test',
        'source' => 'https://www.example.com/article',
        'published_date' => '2025-12-28T08:00:00Z',
    ];

    $job = new ProcessIncomingArticle($articleData);
    $job->handle();

    $article = Article::where('external_id', 'es-doc-id-www-test')->first();
    $source = $article->newsSource;

    expect($source->name)->toBe('Example');
    expect($source->slug)->toBe('example');
});

it('creates unknown source when URL cannot be parsed', function () {
    $articleData = [
        'publisher' => [
            'route_id' => 'test-route',
            'published_at' => '2025-12-28T15:30:45Z',
            'channel' => 'articles:crime',
        ],
        'id' => 'es-doc-id-invalid-url',
        'title' => 'Test Article',
        'body' => 'Content',
        'canonical_url' => 'https://example.com/test',
        'source' => 'invalid-url',
        'published_date' => '2025-12-28T08:00:00Z',
    ];

    $job = new ProcessIncomingArticle($articleData);
    $job->handle();

    $article = Article::where('external_id', 'es-doc-id-invalid-url')->first();
    $source = $article->newsSource;

    expect($source->slug)->toBe('unknown');
    expect($source->name)->toBe('Unknown Source');
});

it('maps topics to tags', function () {
    $articleData = [
        'publisher' => [
            'route_id' => 'test-route',
            'published_at' => '2025-12-28T15:30:45Z',
            'channel' => 'articles:crime',
        ],
        'id' => 'es-doc-id-topics-test',
        'title' => 'Test Article',
        'body' => 'Content',
        'canonical_url' => 'https://example.com/test',
        'source' => 'https://example.com',
        'published_date' => '2025-12-28T08:00:00Z',
        'topics' => ['crime', 'local', 'police'],
    ];

    $job = new ProcessIncomingArticle($articleData);
    $job->handle();

    $article = Article::where('external_id', 'es-doc-id-topics-test')->first();

    expect($article->tags)->toHaveCount(3);
    expect($article->tags->pluck('slug')->toArray())->toContain('crime', 'local', 'police');
});

it('stores publisher metadata in article metadata field', function () {
    $articleData = [
        'publisher' => [
            'route_id' => 'a1b2c3d4-e5f6-4789-a0b1-c2d3e4f5g6h7',
            'published_at' => '2025-12-28T15:30:45Z',
            'channel' => 'articles:crime',
        ],
        'id' => 'es-doc-id-metadata-test',
        'title' => 'Test Article',
        'body' => 'Content',
        'canonical_url' => 'https://example.com/test',
        'source' => 'https://example.com',
        'published_date' => '2025-12-28T08:00:00Z',
        'quality_score' => 85,
        'source_reputation' => 78,
        'confidence' => 0.92,
        'is_crime_related' => true,
        'content_type' => 'article',
        'word_count' => 450,
        'category' => 'news',
        'section' => 'local',
        'keywords' => ['police', 'investigation'],
    ];

    $job = new ProcessIncomingArticle($articleData);
    $job->handle();

    $article = Article::where('external_id', 'es-doc-id-metadata-test')->first();
    $metadata = $article->metadata;

    expect($metadata['publisher']['route_id'])->toBe('a1b2c3d4-e5f6-4789-a0b1-c2d3e4f5g6h7');
    expect($metadata['publisher']['channel'])->toBe('articles:crime');
    expect($metadata['quality_score'])->toBe(85);
    expect($metadata['source_reputation'])->toBe(78);
    expect($metadata['confidence'])->toBe(0.92);
    expect($metadata['is_crime_related'])->toBe(true);
    expect($metadata['content_type'])->toBe('article');
    expect($metadata['word_count'])->toBe(450);
    expect($metadata['category'])->toBe('news');
    expect($metadata['section'])->toBe('local');
    expect($metadata['keywords'])->toBe(['police', 'investigation']);
});

it('skips duplicate articles using external_id', function () {
    $articleData = [
        'publisher' => [
            'route_id' => 'test-route',
            'published_at' => '2025-12-28T15:30:45Z',
            'channel' => 'articles:crime',
        ],
        'id' => 'es-doc-id-duplicate',
        'title' => 'Test Article',
        'body' => 'Content',
        'canonical_url' => 'https://example.com/test',
        'source' => 'https://example.com',
        'published_date' => '2025-12-28T08:00:00Z',
    ];

    // Create article first
    Article::factory()->create([
        'external_id' => 'es-doc-id-duplicate',
        'title' => 'Original Article',
    ]);

    $initialCount = Article::count();

    $job = new ProcessIncomingArticle($articleData);
    $job->handle();

    // Article count should not increase
    expect(Article::count())->toBe($initialCount);
});

it('rejects message with missing required fields', function () {
    $articleData = [
        'title' => 'Test Article',
        // Missing: id, canonical_url, source, published_date
    ];

    $job = new ProcessIncomingArticle($articleData);
    $job->handle();

    $article = Article::where('title', 'Test Article')->first();

    expect($article)->toBeNull();
});

it('uses description as excerpt when intro is not present', function () {
    $articleData = [
        'publisher' => [
            'route_id' => 'test-route',
            'published_at' => '2025-12-28T15:30:45Z',
            'channel' => 'articles:crime',
        ],
        'id' => 'es-doc-id-description',
        'title' => 'Test Article',
        'body' => 'Content',
        'canonical_url' => 'https://example.com/test',
        'source' => 'https://example.com',
        'published_date' => '2025-12-28T08:00:00Z',
        'description' => 'Meta description of the article',
    ];

    $job = new ProcessIncomingArticle($articleData);
    $job->handle();

    $article = Article::where('external_id', 'es-doc-id-description')->first();

    expect($article->excerpt)->toBe('Meta description of the article');
});

it('updates source credibility_score when source_reputation is provided', function () {
    $articleData = [
        'publisher' => [
            'route_id' => 'test-route',
            'published_at' => '2025-12-28T15:30:45Z',
            'channel' => 'articles:crime',
        ],
        'id' => 'es-doc-id-reputation',
        'title' => 'Test Article',
        'body' => 'Content',
        'canonical_url' => 'https://example.com/test',
        'source' => 'https://example.com',
        'published_date' => '2025-12-28T08:00:00Z',
        'source_reputation' => 85,
    ];

    $job = new ProcessIncomingArticle($articleData);
    $job->handle();

    $article = Article::where('external_id', 'es-doc-id-reputation')->first();
    $source = $article->newsSource;

    expect($source->credibility_score)->toBe(85);
});

it('handles empty topics array', function () {
    $articleData = [
        'publisher' => [
            'route_id' => 'test-route',
            'published_at' => '2025-12-28T15:30:45Z',
            'channel' => 'articles:crime',
        ],
        'id' => 'es-doc-id-no-topics',
        'title' => 'Test Article',
        'body' => 'Content',
        'canonical_url' => 'https://example.com/test',
        'source' => 'https://example.com',
        'published_date' => '2025-12-28T08:00:00Z',
        'topics' => [],
    ];

    $job = new ProcessIncomingArticle($articleData);
    $job->handle();

    $article = Article::where('external_id', 'es-doc-id-no-topics')->first();

    expect($article->tags)->toHaveCount(0);
});

it('sanitizes HTML content while preserving basic formatting', function () {
    $articleData = [
        'publisher' => [
            'route_id' => 'test-route',
            'published_at' => '2025-12-28T15:30:45Z',
            'channel' => 'articles:crime',
        ],
        'id' => 'es-doc-id-sanitize',
        'title' => 'Test Article',
        'body' => '<p>Valid paragraph</p><script>alert("xss")</script><strong>Bold text</strong>',
        'canonical_url' => 'https://example.com/test',
        'source' => 'https://example.com',
        'published_date' => '2025-12-28T08:00:00Z',
    ];

    $job = new ProcessIncomingArticle($articleData);
    $job->handle();

    $article = Article::where('external_id', 'es-doc-id-sanitize')->first();

    expect($article->content)->toContain('<p>Valid paragraph</p>');
    expect($article->content)->toContain('<strong>Bold text</strong>');
    expect($article->content)->not->toContain('<script>');
});

it('processes article with og_title instead of title', function () {
    $articleData = [
        'publisher' => [
            'route_id' => 'test-route',
            'published_at' => '2025-12-28T15:30:45Z',
            'channel' => 'articles:crime',
        ],
        'id' => 'es-doc-id-og-title',
        'og_title' => 'Article with OG Title',
        'body' => 'Content here',
        'canonical_url' => '',
        'published_date' => '0001-01-01T00:00:00Z',
    ];

    $job = new ProcessIncomingArticle($articleData);
    $job->handle();

    $article = Article::where('external_id', 'es-doc-id-og-title')->first();

    expect($article)->not->toBeNull();
    expect($article->title)->toBe('Article with OG Title');
    expect($article->url)->toContain('articles:crime');
});

it('processes article with missing source field', function () {
    $articleData = [
        'publisher' => [
            'route_id' => 'test-route',
            'published_at' => '2025-12-28T15:30:45Z',
            'channel' => 'articles:crime',
        ],
        'id' => 'es-doc-id-no-source',
        'og_title' => 'Article without source',
        'body' => 'Content',
        'canonical_url' => '',
    ];

    $job = new ProcessIncomingArticle($articleData);
    $job->handle();

    $article = Article::where('external_id', 'es-doc-id-no-source')->first();

    expect($article)->not->toBeNull();
    expect($article->newsSource)->not->toBeNull();
    expect($article->newsSource->slug)->toBe('crime');
});

it('uses publisher published_at when published_date is invalid', function () {
    $articleData = [
        'publisher' => [
            'route_id' => 'test-route',
            'published_at' => '2025-12-28T15:30:45Z',
            'channel' => 'articles:crime',
        ],
        'id' => 'es-doc-id-invalid-date',
        'title' => 'Test Article',
        'body' => 'Content',
        'canonical_url' => 'https://example.com/test',
        'published_date' => '0001-01-01T00:00:00Z',
    ];

    $job = new ProcessIncomingArticle($articleData);
    $job->handle();

    $article = Article::where('external_id', 'es-doc-id-invalid-date')->first();

    expect($article)->not->toBeNull();
    expect($article->published_at->toIso8601String())->toBe('2025-12-28T15:30:45+00:00');
});

it('uses og_description as excerpt when intro and description are missing', function () {
    $articleData = [
        'publisher' => [
            'route_id' => 'test-route',
            'published_at' => '2025-12-28T15:30:45Z',
            'channel' => 'articles:crime',
        ],
        'id' => 'es-doc-id-og-description',
        'title' => 'Test Article',
        'body' => 'Content',
        'canonical_url' => 'https://example.com/test',
        'published_date' => '2025-12-28T08:00:00Z',
        'og_description' => 'Open Graph description',
    ];

    $job = new ProcessIncomingArticle($articleData);
    $job->handle();

    $article = Article::where('external_id', 'es-doc-id-og-description')->first();

    expect($article->excerpt)->toBe('Open Graph description');
});
