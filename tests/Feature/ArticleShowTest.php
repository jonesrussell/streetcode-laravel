<?php

use App\Models\Article;
use App\Models\City;

test('article show page returns successful response', function () {
    $article = Article::factory()->published()->create();

    $response = $this->get(route('articles.show', $article));

    $response->assertSuccessful();
});

test('article show page contains article data', function () {
    $article = Article::factory()->published()->create([
        'title' => 'Test Article Title',
        'excerpt' => 'This is a test excerpt for the article.',
    ]);

    $response = $this->get(route('articles.show', $article));

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Articles/Show')
            ->has('article')
            ->where('article.title', 'Test Article Title')
            ->where('article.excerpt', 'This is a test excerpt for the article.')
        );
});

test('article show page increments view count', function () {
    $article = Article::factory()->published()->create([
        'view_count' => 5,
    ]);

    $this->get(route('articles.show', $article));

    expect($article->fresh()->view_count)->toBe(6);
});

test('article show page renders when article has image url that may fail to load', function () {
    $article = Article::factory()->published()->create([
        'image_url' => 'https://example.com/may-404.jpg',
    ]);

    $response = $this->get(route('articles.show', $article));

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Articles/Show')
            ->has('article')
            ->where('article.image_url', 'https://example.com/may-404.jpg')
        );
});

test('article show page includes SEO meta and canonical URL in props for crawlers and sharing', function () {
    $article = Article::factory()->published()->create([
        'title' => 'SEO Test Article',
        'excerpt' => 'Short excerpt for meta description.',
        'image_url' => 'https://example.com/og-image.jpg',
    ]);

    $response = $this->get(route('articles.show', $article));

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Articles/Show')
            ->has('article')
            ->has('canonicalUrl')
            ->has('ogImage')
            ->where('article.title', 'SEO Test Article')
            ->where('ogImage', 'https://example.com/og-image.jpg')
        );

    $props = $response->original->getData()['page']['props'];
    expect($props['canonicalUrl'])->toContain('/articles/');
    expect($props['canonicalUrl'])->not->toEndWith('/');
});

test('article show page uses default og image when article has no image', function () {
    $article = Article::factory()->published()->create([
        'title' => 'No Image Article',
        'image_url' => null,
    ]);

    $response = $this->get(route('articles.show', $article));

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Articles/Show')
            ->has('ogImage')
        );

    $props = $response->original->getData()['page']['props'];
    expect($props['ogImage'])->toContain('logo.png');
});

test('article show page eager loads city relationship', function () {
    $city = City::factory()->inOntario()->create([
        'city_name' => 'Toronto',
        'city_slug' => 'toronto',
    ]);

    $article = Article::factory()->published()->create([
        'city_id' => $city->id,
    ]);

    $response = $this->get(route('articles.show', $article));

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Articles/Show')
            ->has('article.city')
            ->where('article.city.city_name', 'Toronto')
            ->where('article.city.region_code', 'ON')
            ->where('article.city.url_path', '/crime/ca/on/toronto')
        );
});

test('article show page works without city', function () {
    $article = Article::factory()->published()->create([
        'city_id' => null,
    ]);

    $response = $this->get(route('articles.show', $article));

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Articles/Show')
            ->has('article')
            ->where('article.city', null)
        );
});

test('article show page uses slug in URL', function () {
    $article = Article::factory()->published()->create([
        'title' => 'Test Slug Article',
        'slug' => 'test-slug-article',
    ]);

    $response = $this->get("/articles/{$article->slug}");

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Articles/Show')
            ->where('article.slug', 'test-slug-article')
        );
});

test('old ID-based article URLs redirect to slug-based URLs', function () {
    $article = Article::factory()->published()->create([
        'title' => 'Redirect Test Article',
        'slug' => 'redirect-test-article',
    ]);

    $response = $this->get("/articles/{$article->id}");

    $response->assertRedirect("/articles/{$article->slug}");
    $response->assertStatus(301);
});

test('canonical URL uses slug', function () {
    $article = Article::factory()->published()->create([
        'title' => 'Canonical Slug Test',
        'slug' => 'canonical-slug-test',
    ]);

    $response = $this->get(route('articles.show', $article));

    $props = $response->original->getData()['page']['props'];
    expect($props['canonicalUrl'])->toContain('/articles/canonical-slug-test');
});
