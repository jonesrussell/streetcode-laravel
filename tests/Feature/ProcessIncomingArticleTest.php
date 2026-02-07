<?php

use App\Models\Article;
use JonesRussell\NorthCloud\Jobs\ProcessIncomingArticle;

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
        'topics' => ['violent_crime'],
        'is_crime_related' => true,
        'crime_relevance' => 'core_street_crime',
        'source_reputation' => 78,
        'confidence' => 0.92,
        'intro' => 'Article introduction paragraph',
        'og_image' => 'https://example.com/images/article-image.jpg',
    ];

    ProcessIncomingArticle::dispatchSync($articleData);

    $article = Article::where('external_id', 'es-doc-id-12345')->first();

    expect($article)->not->toBeNull();
    expect($article->title)->toBe('Local Police Investigate Break-In');
    expect($article->slug)->not->toBeEmpty();
    expect($article->status)->toBe('published');
    expect($article->url)->toBe('https://example.com/articles/police-investigate');
    expect($article->content)->toBe('Full article text content here...');
    expect($article->excerpt)->toBe('Article introduction paragraph');
    expect($article->image_url)->toBe('https://example.com/images/article-image.jpg');
    expect($article->published_at->toIso8601String())->toBe('2025-12-28T08:00:00+00:00');
});

it('creates news source from URL domain', function () {
    $articleData = [
        'id' => 'es-doc-id-source-test',
        'title' => 'Test Article',
        'body' => 'Content',
        'canonical_url' => 'https://news.example.com/test',
        'source' => 'https://news.example.com/article',
        'published_date' => '2025-12-28T08:00:00Z',
        'crime_relevance' => 'core_street_crime',
    ];

    ProcessIncomingArticle::dispatchSync($articleData);

    $article = Article::where('external_id', 'es-doc-id-source-test')->first();
    $source = $article->newsSource;

    expect($source)->not->toBeNull();
    expect($source->slug)->toBe('news-example-com');
});

it('handles www prefix in source URL', function () {
    $articleData = [
        'id' => 'es-doc-id-www-test',
        'title' => 'Test Article',
        'body' => 'Content',
        'canonical_url' => 'https://www.example.com/test',
        'published_date' => '2025-12-28T08:00:00Z',
        'crime_relevance' => 'core_street_crime',
    ];

    ProcessIncomingArticle::dispatchSync($articleData);

    $article = Article::where('external_id', 'es-doc-id-www-test')->first();
    $source = $article->newsSource;

    expect($source->slug)->toBe('example-com');
});

it('creates unknown source when no URL available', function () {
    $articleData = [
        'id' => 'es-doc-id-no-url',
        'title' => 'Test Article',
        'body' => 'Content',
        'crime_relevance' => 'core_street_crime',
    ];

    ProcessIncomingArticle::dispatchSync($articleData);

    $article = Article::where('external_id', 'es-doc-id-no-url')->first();

    expect($article)->not->toBeNull();
    expect($article->newsSource->slug)->toBe('unknown');
});

it('maps topics to tags', function () {
    $articleData = [
        'id' => 'es-doc-id-topics-test',
        'title' => 'Test Article',
        'body' => 'Content',
        'canonical_url' => 'https://example.com/test',
        'source' => 'https://example.com',
        'published_date' => '2025-12-28T08:00:00Z',
        'crime_relevance' => 'core_street_crime',
        'topics' => ['violent_crime', 'property_crime', 'gang_violence'],
    ];

    ProcessIncomingArticle::dispatchSync($articleData);

    $article = Article::where('external_id', 'es-doc-id-topics-test')->first();

    expect($article->tags)->toHaveCount(3);
    expect($article->tags->pluck('slug')->toArray())->toContain('violent-crime', 'property-crime', 'gang-violence');
});

it('stores metadata in article metadata field', function () {
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
        'crime_relevance' => 'core_street_crime',
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

    ProcessIncomingArticle::dispatchSync($articleData);

    $article = Article::where('external_id', 'es-doc-id-metadata-test')->first();
    $metadata = $article->metadata;

    expect($metadata['publisher']['route_id'])->toBe('a1b2c3d4-e5f6-4789-a0b1-c2d3e4f5g6h7');
    expect($metadata['publisher']['channel'])->toBe('articles:crime');
    expect($metadata['quality_score'])->toBe(85);
    expect($metadata['confidence'])->toBe(0.92);
    expect($metadata['is_crime_related'])->toBe(true);
    expect($metadata['content_type'])->toBe('article');
    expect($metadata['word_count'])->toBe(450);
    expect($metadata['category'])->toBe('news');
    expect($metadata['section'])->toBe('local');
    expect($metadata['keywords'])->toBe(['police', 'investigation']);
});

it('skips duplicate articles using external_id', function () {
    Article::factory()->create([
        'external_id' => 'es-doc-id-duplicate',
        'title' => 'Original Article',
    ]);

    $initialCount = Article::count();

    $articleData = [
        'id' => 'es-doc-id-duplicate',
        'title' => 'Test Article',
        'body' => 'Content',
        'canonical_url' => 'https://example.com/test',
        'source' => 'https://example.com',
        'published_date' => '2025-12-28T08:00:00Z',
        'crime_relevance' => 'core_street_crime',
    ];

    ProcessIncomingArticle::dispatchSync($articleData);

    expect(Article::count())->toBe($initialCount);
});

it('rejects message with missing required fields', function () {
    $articleData = [
        'title' => 'Test Article',
    ];

    ProcessIncomingArticle::dispatchSync($articleData);

    $article = Article::where('title', 'Test Article')->first();

    expect($article)->toBeNull();
});

it('uses og_description as excerpt when intro is missing', function () {
    $articleData = [
        'id' => 'es-doc-id-og-description',
        'title' => 'Test Article',
        'body' => 'Content',
        'canonical_url' => 'https://example.com/test',
        'published_date' => '2025-12-28T08:00:00Z',
        'crime_relevance' => 'core_street_crime',
        'og_description' => 'Open Graph description',
    ];

    ProcessIncomingArticle::dispatchSync($articleData);

    $article = Article::where('external_id', 'es-doc-id-og-description')->first();

    expect($article->excerpt)->toBe('Open Graph description');
});

it('handles empty topics array', function () {
    $articleData = [
        'id' => 'es-doc-id-no-topics',
        'title' => 'Test Article',
        'body' => 'Content',
        'canonical_url' => 'https://example.com/test',
        'source' => 'https://example.com',
        'published_date' => '2025-12-28T08:00:00Z',
        'crime_relevance' => 'core_street_crime',
        'topics' => [],
    ];

    ProcessIncomingArticle::dispatchSync($articleData);

    $article = Article::where('external_id', 'es-doc-id-no-topics')->first();

    expect($article->tags)->toHaveCount(0);
});

it('sanitizes HTML content while preserving basic formatting', function () {
    $articleData = [
        'id' => 'es-doc-id-sanitize',
        'title' => 'Test Article',
        'body' => '<p>Valid paragraph</p><script>alert("xss")</script><strong>Bold text</strong>',
        'canonical_url' => 'https://example.com/test',
        'source' => 'https://example.com',
        'published_date' => '2025-12-28T08:00:00Z',
        'crime_relevance' => 'core_street_crime',
    ];

    ProcessIncomingArticle::dispatchSync($articleData);

    $article = Article::where('external_id', 'es-doc-id-sanitize')->first();

    expect($article->content)->toContain('<p>Valid paragraph</p>');
    expect($article->content)->toContain('<strong>Bold text</strong>');
    expect($article->content)->not->toContain('<script>');
});

it('processes article with og_title instead of title', function () {
    $articleData = [
        'id' => 'es-doc-id-og-title',
        'og_title' => 'Article with OG Title',
        'body' => 'Content here',
        'canonical_url' => 'https://example.com/og-article',
        'published_date' => '0001-01-01T00:00:00Z',
        'crime_relevance' => 'core_street_crime',
    ];

    ProcessIncomingArticle::dispatchSync($articleData);

    $article = Article::where('external_id', 'es-doc-id-og-title')->first();

    expect($article)->not->toBeNull();
    expect($article->title)->toBe('Article with OG Title');
});

it('filters non-crime topics from tags', function () {
    $articleData = [
        'id' => 'es-doc-id-filter-topics',
        'title' => 'Test Article',
        'body' => 'Content',
        'canonical_url' => 'https://example.com/test',
        'source' => 'https://example.com',
        'published_date' => '2025-12-28T08:00:00Z',
        'crime_relevance' => 'core_street_crime',
        'topics' => ['violent_crime', 'local', 'police', 'drug_crime'],
    ];

    ProcessIncomingArticle::dispatchSync($articleData);

    $article = Article::where('external_id', 'es-doc-id-filter-topics')->first();

    expect($article->tags)->toHaveCount(2);
    expect($article->tags->pluck('slug')->toArray())->toContain('violent-crime', 'drug-crime');
    expect($article->tags->pluck('slug')->toArray())->not->toContain('local', 'police');
});

it('rejects non-core crime article', function () {
    $data = [
        'id' => 'test-reject-peripheral',
        'title' => 'Opinion about crime rates',
        'canonical_url' => 'https://example.com/opinion',
        'source' => 'https://example.com',
        'quality_score' => 75,
        'crime_relevance' => 'peripheral_crime',
    ];

    ProcessIncomingArticle::dispatchSync($data);

    expect(Article::where('external_id', 'test-reject-peripheral')->exists())->toBeFalse();
});

it('rejects article with missing crime_relevance', function () {
    $data = [
        'id' => 'test-reject-no-relevance',
        'title' => 'Article without crime relevance field',
        'canonical_url' => 'https://example.com/article',
        'source' => 'https://example.com',
        'quality_score' => 75,
    ];

    ProcessIncomingArticle::dispatchSync($data);

    expect(Article::where('external_id', 'test-reject-no-relevance')->exists())->toBeFalse();
});

it('accepts core_street_crime article', function () {
    $data = [
        'id' => 'test-accept-core',
        'title' => 'Man arrested for robbery',
        'canonical_url' => 'https://example.com/robbery',
        'source' => 'https://example.com',
        'quality_score' => 75,
        'crime_relevance' => 'core_street_crime',
        'topics' => ['violent_crime'],
    ];

    ProcessIncomingArticle::dispatchSync($data);

    expect(Article::where('external_id', 'test-accept-core')->exists())->toBeTrue();
});

it('falls back to publisher published_at when published_date is missing', function () {
    $articleData = [
        'publisher' => [
            'route_id' => 'test-route',
            'published_at' => '2025-12-28T15:30:45Z',
            'channel' => 'articles:crime',
        ],
        'id' => 'es-doc-id-fallback-date',
        'title' => 'Test Article',
        'body' => 'Content',
        'canonical_url' => 'https://example.com/test',
        'crime_relevance' => 'core_street_crime',
    ];

    ProcessIncomingArticle::dispatchSync($articleData);

    $article = Article::where('external_id', 'es-doc-id-fallback-date')->first();

    expect($article)->not->toBeNull();
    expect($article->published_at->toIso8601String())->toBe('2025-12-28T15:30:45+00:00');
});
