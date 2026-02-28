<?php

use App\Models\Article;
use App\Models\Tag;

test('tag index page displays all crime categories', function () {
    $tags = Tag::factory()->crimeCategory()->count(3)->create();

    $response = $this->get('/tags');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Tags/Index')
        ->has('crimeCategories', 3)
    );
});

test('tag show page displays tag with articles', function () {
    $tag = Tag::factory()->crimeCategory()->create([
        'name' => 'Violent Crime',
        'slug' => 'violent-crime',
        'article_count' => 5,
    ]);

    $articles = Article::factory()->published()->count(3)->create();
    foreach ($articles as $article) {
        $article->tags()->attach($tag->id, ['confidence' => 0.9]);
    }

    $response = $this->get('/tags/violent-crime');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Tags/Show')
        ->where('tag.slug', 'violent-crime')
        ->where('tag.name', 'Violent Crime')
        ->has('articles.data', 3)
    );
});

test('tag show page returns 404 for nonexistent tag', function () {
    $this->get('/tags/nonexistent-tag')->assertNotFound();
});

test('tag show page orders articles by published_at descending', function () {
    $tag = Tag::factory()->crimeCategory()->create(['slug' => 'drug-crime']);

    $oldArticle = Article::factory()->published()->create([
        'title' => 'Old Article',
        'published_at' => now()->subDays(5),
    ]);
    $newArticle = Article::factory()->published()->create([
        'title' => 'New Article',
        'published_at' => now(),
    ]);

    $oldArticle->tags()->attach($tag->id);
    $newArticle->tags()->attach($tag->id);

    $response = $this->get('/tags/drug-crime');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Tags/Show')
        ->where('articles.data.0.title', 'New Article')
        ->where('articles.data.1.title', 'Old Article')
    );
});

test('tag show page paginates articles', function () {
    $tag = Tag::factory()->crimeCategory()->create(['slug' => 'property-crime']);

    $articles = Article::factory()->published()->count(25)->create();
    foreach ($articles as $article) {
        $article->tags()->attach($tag->id);
    }

    $response = $this->get('/tags/property-crime');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Tags/Show')
        ->has('articles.data', 20)
        ->where('articles.last_page', 2)
    );
});
