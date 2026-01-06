<?php

use App\Models\Article;

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
