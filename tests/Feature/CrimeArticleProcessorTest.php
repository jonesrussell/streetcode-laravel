<?php

use App\Models\Article;
use App\Models\City;
use App\Processing\CrimeArticleProcessor;

beforeEach(function () {
    $this->processor = app(CrimeArticleProcessor::class);
});

it('rejects non-core crime article', function () {
    $data = [
        'id' => 'test-reject-peripheral',
        'title' => 'Opinion about crime rates',
        'canonical_url' => 'https://example.com/opinion',
        'crime_relevance' => 'peripheral_crime',
    ];

    $result = $this->processor->process($data, null);

    expect($result)->toBeNull();
    expect(Article::where('external_id', 'test-reject-peripheral')->exists())->toBeFalse();
});

it('rejects article with missing crime_relevance', function () {
    $data = [
        'id' => 'test-no-relevance',
        'title' => 'Article without classification',
        'canonical_url' => 'https://example.com/article',
    ];

    $result = $this->processor->process($data, null);

    expect($result)->toBeNull();
});

it('processes core_street_crime article', function () {
    $data = [
        'id' => 'test-core-crime',
        'title' => 'Man arrested for robbery',
        'canonical_url' => 'https://example.com/robbery',
        'source' => 'https://example.com',
        'published_date' => '2025-12-28T08:00:00Z',
        'crime_relevance' => 'core_street_crime',
        'intro' => 'A man was arrested.',
        'body' => 'Full content here.',
        'topics' => ['violent_crime'],
    ];

    $result = $this->processor->process($data, null);

    expect($result)->not->toBeNull();
    expect($result->title)->toBe('Man arrested for robbery');
    expect($result->slug)->not->toBeEmpty();
    expect($result->status)->toBe('published');
    expect($result->external_id)->toBe('test-core-crime');
});

it('links city from location data', function () {
    $data = [
        'id' => 'test-city-link',
        'title' => 'Local crime report',
        'canonical_url' => 'https://example.com/local',
        'source' => 'https://example.com',
        'crime_relevance' => 'core_street_crime',
        'body' => 'Content',
        'location_city' => 'toronto',
        'location_province' => 'ON',
        'location_country' => 'Canada',
    ];

    $result = $this->processor->process($data, null);

    expect($result)->not->toBeNull();
    expect($result->city_id)->not->toBeNull();

    $city = City::find($result->city_id);
    expect($city->city_slug)->toBe('toronto');
    expect($city->region_code)->toBe('ON');
});

it('merges extended metadata', function () {
    $data = [
        'id' => 'test-metadata',
        'title' => 'Test metadata article',
        'canonical_url' => 'https://example.com/test',
        'source' => 'https://example.com',
        'crime_relevance' => 'core_street_crime',
        'body' => 'Content',
        'confidence' => 0.92,
        'is_crime_related' => true,
        'content_type' => 'article',
        'word_count' => 450,
        'category' => 'news',
        'section' => 'local',
        'keywords' => ['police', 'investigation'],
        'og_title' => 'OG Title',
        'og_description' => 'OG Desc',
        'location_city' => 'ottawa',
        'location_province' => 'ON',
        'location_country' => 'Canada',
        'location_confidence' => 0.85,
    ];

    $result = $this->processor->process($data, null);
    $metadata = $result->fresh()->metadata;

    expect($metadata['confidence'])->toBe(0.92);
    expect($metadata['is_crime_related'])->toBe(true);
    expect($metadata['content_type'])->toBe('article');
    expect($metadata['word_count'])->toBe(450);
    expect($metadata['category'])->toBe('news');
    expect($metadata['section'])->toBe('local');
    expect($metadata['keywords'])->toBe(['police', 'investigation']);
    expect($metadata['og_title'])->toBe('OG Title');
    expect($metadata['location_city'])->toBe('ottawa');
    expect($metadata['location_confidence'])->toBe(0.85);
});

it('filters tags to allowed crime types only', function () {
    $data = [
        'id' => 'test-tag-filter',
        'title' => 'Test tag filtering',
        'canonical_url' => 'https://example.com/tags',
        'source' => 'https://example.com',
        'crime_relevance' => 'core_street_crime',
        'body' => 'Content',
        'topics' => ['violent_crime', 'local', 'police', 'drug_crime'],
    ];

    $result = $this->processor->process($data, null);

    expect($result->tags)->toHaveCount(2);
    $slugs = $result->tags->pluck('slug')->toArray();
    expect($slugs)->toContain('violent-crime', 'drug-crime');
    expect($slugs)->not->toContain('local', 'police');
});

it('handles empty topics', function () {
    $data = [
        'id' => 'test-no-topics',
        'title' => 'No topics article',
        'canonical_url' => 'https://example.com/no-topics',
        'source' => 'https://example.com',
        'crime_relevance' => 'core_street_crime',
        'body' => 'Content',
        'topics' => [],
    ];

    $result = $this->processor->process($data, null);

    expect($result->tags)->toHaveCount(0);
});

it('skips duplicate articles', function () {
    Article::factory()->create(['external_id' => 'test-dupe']);

    $data = [
        'id' => 'test-dupe',
        'title' => 'Duplicate article',
        'canonical_url' => 'https://example.com/dupe',
        'crime_relevance' => 'core_street_crime',
    ];

    $result = $this->processor->process($data, null);

    expect($result)->toBeNull();
});

it('skips city linking when location data is incomplete', function () {
    $data = [
        'id' => 'test-no-location',
        'title' => 'No location data',
        'canonical_url' => 'https://example.com/no-loc',
        'source' => 'https://example.com',
        'crime_relevance' => 'core_street_crime',
        'body' => 'Content',
    ];

    $result = $this->processor->process($data, null);

    expect($result)->not->toBeNull();
    expect($result->city_id)->toBeNull();
});
