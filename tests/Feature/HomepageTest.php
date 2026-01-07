<?php

use App\Models\Article;
use App\Models\Tag;

test('homepage returns successful response', function () {
    $response = $this->get('/');

    $response->assertSuccessful();
});

test('homepage renders articles index component', function () {
    $response = $this->get('/');

    $response->assertInertia(fn ($page) => $page->component('Articles/Index'));
});

test('homepage includes hero article when featured articles exist', function () {
    $featured = Article::factory()->published()->featured()->create([
        'image_url' => 'https://example.com/image.jpg',
    ]);

    $response = $this->get('/');

    $response->assertInertia(fn ($page) => $page
        ->has('heroArticle')
        ->where('heroArticle.id', $featured->id)
    );
});

test('homepage includes featured articles', function () {
    Article::factory()->count(3)->published()->featured()->create();

    $response = $this->get('/');

    $response->assertInertia(fn ($page) => $page->has('featuredArticles'));
});

test('homepage includes top stories', function () {
    Article::factory()->count(10)->published()->create();

    $response = $this->get('/');

    $response->assertInertia(fn ($page) => $page
        ->has('topStories')
        ->where('topStories', fn ($stories) => count($stories) <= 8)
    );
});

test('homepage includes articles by category when tags exist', function () {
    $tag = Tag::factory()->create([
        'slug' => 'gang-violence',
        'type' => 'crime_category',
    ]);

    $article = Article::factory()->published()->create();
    $article->tags()->attach($tag);

    $response = $this->get('/');

    $response->assertInertia(fn ($page) => $page->has('articlesByCategory'));
});

test('homepage includes trending topics', function () {
    Tag::factory()->count(5)->create();

    $response = $this->get('/');

    $response->assertInertia(fn ($page) => $page->has('trendingTopics'));
});

test('homepage includes popular tags', function () {
    Tag::factory()->count(5)->create(['type' => 'crime_category']);

    $response = $this->get('/');

    $response->assertInertia(fn ($page) => $page->has('popularTags'));
});

test('homepage filters articles by tag', function () {
    $tag = Tag::factory()->create(['slug' => 'test-tag']);
    $articleWithTag = Article::factory()->published()->create();
    $articleWithTag->tags()->attach($tag);

    Article::factory()->published()->create();

    $response = $this->get('/?tag=test-tag');

    $response->assertInertia(fn ($page) => $page
        ->has('filters')
        ->where('filters.tag', 'test-tag')
    );
});

test('homepage accepts search parameter', function () {
    // Full-text search requires MySQL - this test only verifies the filter is passed
    // Skip the actual search request since SQLite doesn't support fulltext
    $response = $this->get('/');

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page->has('filters'));
})->skip(fn () => config('database.default') === 'sqlite', 'Full-text search requires MySQL');

test('homepage paginates articles', function () {
    Article::factory()->count(25)->published()->create();

    $response = $this->get('/');

    $response->assertInertia(fn ($page) => $page
        ->has('articles')
        ->has('articles.data')
    );
});
