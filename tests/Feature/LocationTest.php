<?php

use App\Jobs\ProcessIncomingArticle;
use App\Models\Article;
use App\Models\City;
use App\Models\NewsSource;

test('city is auto-created when article arrives with location data', function () {
    $source = NewsSource::factory()->create();

    $articleData = [
        'id' => 'loc-test-1',
        'title' => 'Sudbury Police arrest suspect in downtown robbery',
        'body' => 'A man was arrested today in Greater Sudbury.',
        'canonical_url' => 'https://example.com/sudbury-arrest',
        'source' => 'https://example.com',
        'published_date' => now()->toIso8601String(),
        'publisher' => [
            'route_id' => null,
            'published_at' => now()->toIso8601String(),
            'channel' => 'crime:homepage',
        ],
        'crime_relevance' => 'core_street_crime',
        'quality_score' => 80,
        'location_city' => 'sudbury',
        'location_province' => 'ON',
        'location_country' => 'canada',
        'location_confidence' => 0.87,
    ];

    ProcessIncomingArticle::dispatchSync($articleData);

    $article = Article::where('external_id', 'loc-test-1')->first();
    expect($article)->not->toBeNull();
    expect($article->city_id)->not->toBeNull();

    $city = $article->city;
    expect($city->city_slug)->toBe('sudbury');
    expect($city->region_code)->toBe('ON');
    expect($city->country_code)->toBe('ca');
    expect($city->city_name)->toBe('Sudbury');
    expect($city->region_name)->toBe('Ontario');
    expect($city->country_name)->toBe('Canada');
    expect($city->article_count)->toBe(1);
});

test('duplicate city is not created for same location', function () {
    $source = NewsSource::factory()->create();

    $baseData = [
        'body' => 'Test article body.',
        'canonical_url' => 'https://example.com/',
        'source' => 'https://example.com',
        'published_date' => now()->toIso8601String(),
        'publisher' => ['route_id' => null, 'published_at' => now()->toIso8601String(), 'channel' => 'crime:homepage'],
        'crime_relevance' => 'core_street_crime',
        'quality_score' => 80,
        'location_city' => 'toronto',
        'location_province' => 'ON',
        'location_country' => 'canada',
        'location_confidence' => 0.9,
    ];

    ProcessIncomingArticle::dispatchSync(array_merge($baseData, [
        'id' => 'dup-city-1',
        'title' => 'Toronto article 1',
        'canonical_url' => 'https://example.com/1',
    ]));

    ProcessIncomingArticle::dispatchSync(array_merge($baseData, [
        'id' => 'dup-city-2',
        'title' => 'Toronto article 2',
        'canonical_url' => 'https://example.com/2',
    ]));

    expect(City::where('city_slug', 'toronto')->count())->toBe(1);
    expect(City::where('city_slug', 'toronto')->first()->article_count)->toBe(2);
});

test('article without location data has null city_id', function () {
    $source = NewsSource::factory()->create();

    $articleData = [
        'id' => 'no-loc-1',
        'title' => 'Article without location',
        'body' => 'No location data.',
        'canonical_url' => 'https://example.com/no-loc',
        'source' => 'https://example.com',
        'published_date' => now()->toIso8601String(),
        'publisher' => ['route_id' => null, 'published_at' => now()->toIso8601String(), 'channel' => 'crime:homepage'],
        'crime_relevance' => 'core_street_crime',
        'quality_score' => 80,
    ];

    ProcessIncomingArticle::dispatchSync($articleData);

    $article = Article::where('external_id', 'no-loc-1')->first();
    expect($article->city_id)->toBeNull();
});

test('location metadata is stored in article metadata', function () {
    $source = NewsSource::factory()->create();

    $articleData = [
        'id' => 'meta-loc-1',
        'title' => 'Article with location metadata',
        'body' => 'Body text.',
        'canonical_url' => 'https://example.com/meta-loc',
        'source' => 'https://example.com',
        'published_date' => now()->toIso8601String(),
        'publisher' => ['route_id' => null, 'published_at' => now()->toIso8601String(), 'channel' => 'crime:homepage'],
        'crime_relevance' => 'core_street_crime',
        'quality_score' => 80,
        'location_city' => 'vancouver',
        'location_province' => 'BC',
        'location_country' => 'canada',
        'location_confidence' => 0.75,
    ];

    ProcessIncomingArticle::dispatchSync($articleData);

    $article = Article::where('external_id', 'meta-loc-1')->first();
    expect($article->metadata['location_city'])->toBe('vancouver');
    expect($article->metadata['location_province'])->toBe('BC');
    expect($article->metadata['location_country'])->toBe('canada');
    expect($article->metadata['location_confidence'])->toBe(0.75);
});

test('City::findOrCreateFromLocation resolves display names', function () {
    $city = City::findOrCreateFromLocation('thunder-bay', 'ON', 'canada');

    expect($city->city_name)->toBe('Thunder Bay');
    expect($city->region_name)->toBe('Ontario');
    expect($city->country_name)->toBe('Canada');
    expect($city->country_code)->toBe('ca');
    expect($city->region_code)->toBe('ON');
    expect($city->city_slug)->toBe('thunder-bay');
});

test('article scopes filter by location correctly', function () {
    $toronto = City::factory()->create([
        'city_slug' => 'toronto',
        'region_code' => 'ON',
        'country_code' => 'ca',
    ]);

    $vancouver = City::factory()->create([
        'city_slug' => 'vancouver',
        'region_code' => 'BC',
        'country_code' => 'ca',
    ]);

    Article::factory()->published()->count(3)->inCity($toronto)->create();
    Article::factory()->published()->count(2)->inCity($vancouver)->create();

    expect(Article::inCity($toronto)->count())->toBe(3);
    expect(Article::inCity($vancouver)->count())->toBe(2);
    expect(Article::inRegion('ca', 'ON')->count())->toBe(3);
    expect(Article::inCountry('ca')->count())->toBe(5);
});

test('city page returns correct data', function () {
    $city = City::factory()->create([
        'city_slug' => 'sudbury',
        'city_name' => 'Sudbury',
        'region_code' => 'ON',
        'region_name' => 'Ontario',
        'country_code' => 'ca',
        'country_name' => 'Canada',
    ]);

    Article::factory()->published()->count(3)->inCity($city)->create();

    $response = $this->get('/crime/ca/on/sudbury');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Location/City')
        ->has('location')
        ->where('location.city', 'sudbury')
        ->where('location.cityName', 'Sudbury')
        ->where('location.regionName', 'Ontario')
        ->has('articles.data', 3)
    );
});

test('region page returns correct data', function () {
    $city = City::factory()->create([
        'city_slug' => 'ottawa',
        'region_code' => 'ON',
        'country_code' => 'ca',
        'article_count' => 5,
    ]);

    Article::factory()->published()->count(2)->inCity($city)->create();

    $response = $this->get('/crime/ca/on');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Location/Region')
        ->where('location.region', 'on')
        ->where('location.regionName', 'Ontario')
        ->has('cities')
    );
});

test('country page returns correct data', function () {
    $city = City::factory()->create([
        'city_slug' => 'halifax',
        'region_code' => 'NS',
        'country_code' => 'ca',
        'article_count' => 3,
    ]);

    Article::factory()->published()->count(2)->inCity($city)->create();

    $response = $this->get('/crime/ca');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Location/Country')
        ->where('location.country', 'ca')
        ->where('location.countryName', 'Canada')
        ->has('regions')
        ->has('topCities')
    );
});

test('city page returns 404 for nonexistent city', function () {
    $this->get('/crime/ca/on/fakecity')->assertNotFound();
});

test('country page returns 404 for invalid country', function () {
    $this->get('/crime/xx')->assertNotFound();
});

test('region page returns 404 for invalid region', function () {
    $this->get('/crime/ca/zz')->assertNotFound();
});
