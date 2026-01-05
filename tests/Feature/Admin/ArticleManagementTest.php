<?php

use App\Models\Article;
use App\Models\NewsSource;
use App\Models\Tag;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

// Authorization Tests

it('prevents non-admin users from accessing article index', function () {
    $user = User::factory()->create(['is_admin' => false]);

    actingAs($user)
        ->get(route('dashboard.articles.index'))
        ->assertForbidden();
});

it('prevents non-admin users from accessing article create', function () {
    $user = User::factory()->create(['is_admin' => false]);

    actingAs($user)
        ->get(route('dashboard.articles.create'))
        ->assertForbidden();
});

it('prevents non-admin users from storing articles', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $source = NewsSource::factory()->create();

    actingAs($user)
        ->post(route('dashboard.articles.store'), [
            'title' => 'Test Article',
            'url' => 'https://example.com/test',
            'content' => 'Test content',
            'news_source_id' => $source->id,
        ])
        ->assertForbidden();
});

it('prevents non-admin users from editing articles', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $article = Article::factory()->create();

    actingAs($user)
        ->get(route('dashboard.articles.edit', $article))
        ->assertForbidden();
});

it('prevents non-admin users from updating articles', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $article = Article::factory()->create();

    actingAs($user)
        ->patch(route('dashboard.articles.update', $article), [
            'title' => 'Updated Title',
            'url' => $article->url,
            'content' => $article->content,
            'news_source_id' => $article->news_source_id,
        ])
        ->assertForbidden();
});

it('prevents non-admin users from deleting articles', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $article = Article::factory()->create();

    actingAs($user)
        ->delete(route('dashboard.articles.destroy', $article))
        ->assertForbidden();
});

it('allows admin users to access article index', function () {
    $admin = User::factory()->admin()->create();

    actingAs($admin)
        ->get(route('dashboard.articles.index'))
        ->assertOk();
});

it('redirects guests to login', function () {
    get(route('dashboard.articles.index'))
        ->assertRedirect(route('login'));
});

// CRUD Operation Tests

it('displays articles in admin index', function () {
    $admin = User::factory()->admin()->create();
    $articles = Article::factory()->count(3)->create();

    $response = actingAs($admin)->get(route('dashboard.articles.index'));

    $response->assertOk();
    expect($response->viewData('articles')['data'])->toHaveCount(3);
});

it('displays article stats in admin index', function () {
    $admin = User::factory()->admin()->create();
    Article::factory()->count(5)->published()->create();
    Article::factory()->count(3)->draft()->create();

    $response = actingAs($admin)->get(route('dashboard.articles.index'));

    $stats = $response->viewData('stats');
    expect($stats['total'])->toBe(8);
    expect($stats['published'])->toBe(5);
    expect($stats['drafts'])->toBe(3);
});

it('can create a new article', function () {
    $admin = User::factory()->admin()->create();
    $source = NewsSource::factory()->create();
    $tags = Tag::factory()->count(2)->create();

    $articleData = [
        'title' => 'Test Article',
        'url' => 'https://example.com/test-article',
        'excerpt' => 'Test excerpt',
        'content' => 'Test content',
        'news_source_id' => $source->id,
        'published_at' => now()->toDateTimeString(),
        'is_featured' => false,
        'tags' => $tags->pluck('id')->toArray(),
    ];

    actingAs($admin)
        ->post(route('dashboard.articles.store'), $articleData)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard.articles.index'));

    $article = Article::where('url', 'https://example.com/test-article')->first();

    expect($article)->not->toBeNull();
    expect($article->title)->toBe('Test Article');
    expect($article->author_id)->toBe($admin->id);
    expect($article->tags)->toHaveCount(2);
});

it('can update an existing article', function () {
    $admin = User::factory()->admin()->create();
    $article = Article::factory()->create(['title' => 'Original Title']);

    actingAs($admin)
        ->patch(route('dashboard.articles.update', $article), [
            'title' => 'Updated Title',
            'url' => $article->url,
            'content' => $article->content,
            'news_source_id' => $article->news_source_id,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard.articles.index'));

    expect($article->fresh()->title)->toBe('Updated Title');
});

it('can delete an article', function () {
    $admin = User::factory()->admin()->create();
    $article = Article::factory()->create();

    actingAs($admin)
        ->delete(route('dashboard.articles.destroy', $article))
        ->assertRedirect(route('dashboard.articles.index'));

    expect($article->fresh()->trashed())->toBeTrue();
});

it('displays create form with news sources and tags', function () {
    $admin = User::factory()->admin()->create();
    NewsSource::factory()->count(3)->create();
    Tag::factory()->count(5)->create();

    $response = actingAs($admin)->get(route('dashboard.articles.create'));

    $response->assertOk();
    expect($response->viewData('newsSources'))->toHaveCount(3);
    expect($response->viewData('tags'))->toHaveCount(5);
});

it('displays edit form with article data', function () {
    $admin = User::factory()->admin()->create();
    $article = Article::factory()->create();

    $response = actingAs($admin)->get(route('dashboard.articles.edit', $article));

    $response->assertOk();
    expect($response->viewData('article')->id)->toBe($article->id);
});

// Validation Tests

it('requires required fields', function (string $field) {
    $admin = User::factory()->admin()->create();
    $source = NewsSource::factory()->create();

    $data = [
        'title' => 'Test',
        'url' => 'https://example.com/test',
        'content' => 'Content',
        'news_source_id' => $source->id,
    ];

    unset($data[$field]);

    actingAs($admin)
        ->post(route('dashboard.articles.store'), $data)
        ->assertSessionHasErrors($field);
})->with(['title', 'url', 'content', 'news_source_id']);

it('requires unique url', function () {
    $admin = User::factory()->admin()->create();
    $existing = Article::factory()->create(['url' => 'https://example.com/duplicate']);

    actingAs($admin)
        ->post(route('dashboard.articles.store'), [
            'title' => 'Test',
            'url' => 'https://example.com/duplicate',
            'content' => 'Content',
            'news_source_id' => $existing->news_source_id,
        ])
        ->assertSessionHasErrors('url');
});

it('validates url format', function () {
    $admin = User::factory()->admin()->create();
    $source = NewsSource::factory()->create();

    actingAs($admin)
        ->post(route('dashboard.articles.store'), [
            'title' => 'Test',
            'url' => 'not-a-valid-url',
            'content' => 'Content',
            'news_source_id' => $source->id,
        ])
        ->assertSessionHasErrors('url');
});

it('validates news source exists', function () {
    $admin = User::factory()->admin()->create();

    actingAs($admin)
        ->post(route('dashboard.articles.store'), [
            'title' => 'Test',
            'url' => 'https://example.com/test',
            'content' => 'Content',
            'news_source_id' => 99999,
        ])
        ->assertSessionHasErrors('news_source_id');
});

it('validates tags exist', function () {
    $admin = User::factory()->admin()->create();
    $source = NewsSource::factory()->create();

    actingAs($admin)
        ->post(route('dashboard.articles.store'), [
            'title' => 'Test',
            'url' => 'https://example.com/test',
            'content' => 'Content',
            'news_source_id' => $source->id,
            'tags' => [99999],
        ])
        ->assertSessionHasErrors('tags.0');
});

it('validates excerpt max length', function () {
    $admin = User::factory()->admin()->create();
    $source = NewsSource::factory()->create();

    actingAs($admin)
        ->post(route('dashboard.articles.store'), [
            'title' => 'Test',
            'url' => 'https://example.com/test',
            'content' => 'Content',
            'excerpt' => str_repeat('a', 501),
            'news_source_id' => $source->id,
        ])
        ->assertSessionHasErrors('excerpt');
});

it('allows unique url when updating same article', function () {
    $admin = User::factory()->admin()->create();
    $article = Article::factory()->create();

    actingAs($admin)
        ->patch(route('dashboard.articles.update', $article), [
            'title' => 'Updated',
            'url' => $article->url,
            'content' => $article->content,
            'news_source_id' => $article->news_source_id,
        ])
        ->assertSessionHasNoErrors();
});

// Draft/Published Status Tests

it('can save article as draft', function () {
    $admin = User::factory()->admin()->create();
    $source = NewsSource::factory()->create();

    actingAs($admin)
        ->post(route('dashboard.articles.store'), [
            'title' => 'Draft Article',
            'url' => 'https://example.com/draft',
            'content' => 'Draft content',
            'news_source_id' => $source->id,
            'published_at' => null,
        ])
        ->assertSessionHasNoErrors();

    $article = Article::where('url', 'https://example.com/draft')->first();

    expect($article->published_at)->toBeNull();
});

it('can publish a draft article', function () {
    $admin = User::factory()->admin()->create();
    $draft = Article::factory()->draft()->create();

    actingAs($admin)
        ->patch(route('dashboard.articles.update', $draft), [
            'title' => $draft->title,
            'url' => $draft->url,
            'content' => $draft->content,
            'news_source_id' => $draft->news_source_id,
            'published_at' => now()->toDateTimeString(),
        ])
        ->assertSessionHasNoErrors();

    expect($draft->fresh()->published_at)->not->toBeNull();
});

it('can unpublish a published article', function () {
    $admin = User::factory()->admin()->create();
    $published = Article::factory()->published()->create();

    actingAs($admin)
        ->patch(route('dashboard.articles.update', $published), [
            'title' => $published->title,
            'url' => $published->url,
            'content' => $published->content,
            'news_source_id' => $published->news_source_id,
            'published_at' => null,
        ])
        ->assertSessionHasNoErrors();

    expect($published->fresh()->published_at)->toBeNull();
});

// Tag Management Tests

it('can attach tags when creating article', function () {
    $admin = User::factory()->admin()->create();
    $source = NewsSource::factory()->create();
    $tags = Tag::factory()->count(3)->create();

    actingAs($admin)
        ->post(route('dashboard.articles.store'), [
            'title' => 'Test',
            'url' => 'https://example.com/test',
            'content' => 'Content',
            'news_source_id' => $source->id,
            'tags' => $tags->pluck('id')->toArray(),
        ]);

    $article = Article::where('url', 'https://example.com/test')->first();

    expect($article->tags)->toHaveCount(3);
});

it('can update article tags', function () {
    $admin = User::factory()->admin()->create();
    $article = Article::factory()->create();
    $oldTags = Tag::factory()->count(2)->create();
    $newTags = Tag::factory()->count(3)->create();

    $article->tags()->attach($oldTags);

    actingAs($admin)
        ->patch(route('dashboard.articles.update', $article), [
            'title' => $article->title,
            'url' => $article->url,
            'content' => $article->content,
            'news_source_id' => $article->news_source_id,
            'tags' => $newTags->pluck('id')->toArray(),
        ]);

    expect($article->fresh()->tags)->toHaveCount(3);
    expect($article->fresh()->tags->pluck('id')->toArray())
        ->toMatchArray($newTags->pluck('id')->toArray());
});

it('can remove all tags from article', function () {
    $admin = User::factory()->admin()->create();
    $article = Article::factory()->create();
    $tags = Tag::factory()->count(2)->create();

    $article->tags()->attach($tags);

    actingAs($admin)
        ->patch(route('dashboard.articles.update', $article), [
            'title' => $article->title,
            'url' => $article->url,
            'content' => $article->content,
            'news_source_id' => $article->news_source_id,
            'tags' => [],
        ]);

    expect($article->fresh()->tags)->toHaveCount(0);
});

// Filter and Search Tests

it('can filter articles by status', function () {
    $admin = User::factory()->admin()->create();
    Article::factory()->count(3)->published()->create();
    Article::factory()->count(2)->draft()->create();

    $response = actingAs($admin)
        ->get(route('dashboard.articles.index', ['status' => 'draft']));

    expect($response->viewData('articles')['data'])->toHaveCount(2);
});

it('can search articles by title', function () {
    $admin = User::factory()->admin()->create();
    Article::factory()->create(['title' => 'Laravel Article']);
    Article::factory()->create(['title' => 'Vue Article']);

    // Note: This test may fail if fulltext search is not set up
    // You may need to adjust based on your search implementation
    $response = actingAs($admin)
        ->get(route('dashboard.articles.index', ['search' => 'Laravel']));

    expect($response->viewData('articles')['data'])->toHaveCount(1);
})->skip('Fulltext search requires database setup');

it('can filter articles by source', function () {
    $admin = User::factory()->admin()->create();
    $source1 = NewsSource::factory()->create();
    $source2 = NewsSource::factory()->create();

    Article::factory()->count(2)->create(['news_source_id' => $source1->id]);
    Article::factory()->create(['news_source_id' => $source2->id]);

    $response = actingAs($admin)
        ->get(route('dashboard.articles.index', ['source' => $source1->id]));

    expect($response->viewData('articles')['data'])->toHaveCount(2);
});

it('can sort articles by column', function () {
    $admin = User::factory()->admin()->create();
    Article::factory()->create(['view_count' => 100]);
    Article::factory()->create(['view_count' => 200]);
    Article::factory()->create(['view_count' => 50]);

    $response = actingAs($admin)
        ->get(route('dashboard.articles.index', ['sort' => 'view_count', 'direction' => 'desc']));

    $articles = $response->viewData('articles')['data'];
    expect($articles[0]->view_count)->toBe(200);
    expect($articles[1]->view_count)->toBe(100);
    expect($articles[2]->view_count)->toBe(50);
});

it('paginates articles', function () {
    $admin = User::factory()->admin()->create();
    Article::factory()->count(20)->create();

    $response = actingAs($admin)->get(route('dashboard.articles.index'));

    $articles = $response->viewData('articles');
    expect($articles['data'])->toHaveCount(15);
    expect($articles['meta']['total'])->toBe(20);
});

// Author Tracking Tests

it('sets author_id when creating article', function () {
    $admin = User::factory()->admin()->create();
    $source = NewsSource::factory()->create();

    actingAs($admin)
        ->post(route('dashboard.articles.store'), [
            'title' => 'Test',
            'url' => 'https://example.com/test',
            'content' => 'Content',
            'news_source_id' => $source->id,
        ]);

    $article = Article::where('url', 'https://example.com/test')->first();

    expect($article->author_id)->toBe($admin->id);
});

it('eager loads relationships on index', function () {
    $admin = User::factory()->admin()->create();
    $article = Article::factory()->create();

    $response = actingAs($admin)->get(route('dashboard.articles.index'));

    $articles = $response->viewData('articles')['data'];
    expect($articles[0]->relationLoaded('newsSource'))->toBeTrue();
    expect($articles[0]->relationLoaded('tags'))->toBeTrue();
    expect($articles[0]->relationLoaded('author'))->toBeTrue();
});

// Bulk Action Tests

it('can bulk delete articles', function () {
    $admin = User::factory()->admin()->create();
    $articles = Article::factory()->count(3)->create();

    actingAs($admin)
        ->post(route('dashboard.articles.bulk-delete'), [
            'ids' => $articles->pluck('id')->toArray(),
        ])
        ->assertRedirect(route('dashboard.articles.index'));

    foreach ($articles as $article) {
        expect($article->fresh()->trashed())->toBeTrue();
    }
});

it('prevents non-admin users from bulk deleting articles', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $articles = Article::factory()->count(2)->create();

    actingAs($user)
        ->post(route('dashboard.articles.bulk-delete'), [
            'ids' => $articles->pluck('id')->toArray(),
        ])
        ->assertForbidden();
});

it('validates ids array for bulk delete', function () {
    $admin = User::factory()->admin()->create();

    actingAs($admin)
        ->post(route('dashboard.articles.bulk-delete'), [])
        ->assertSessionHasErrors('ids');
});

it('can bulk publish articles', function () {
    $admin = User::factory()->admin()->create();
    $articles = Article::factory()->count(3)->draft()->create();

    actingAs($admin)
        ->post(route('dashboard.articles.bulk-publish'), [
            'ids' => $articles->pluck('id')->toArray(),
        ])
        ->assertRedirect(route('dashboard.articles.index'));

    foreach ($articles as $article) {
        expect($article->fresh()->published_at)->not->toBeNull();
    }
});

it('prevents non-admin users from bulk publishing articles', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $articles = Article::factory()->count(2)->draft()->create();

    actingAs($user)
        ->post(route('dashboard.articles.bulk-publish'), [
            'ids' => $articles->pluck('id')->toArray(),
        ])
        ->assertForbidden();
});

it('can bulk unpublish articles', function () {
    $admin = User::factory()->admin()->create();
    $articles = Article::factory()->count(3)->published()->create();

    actingAs($admin)
        ->post(route('dashboard.articles.bulk-unpublish'), [
            'ids' => $articles->pluck('id')->toArray(),
        ])
        ->assertRedirect(route('dashboard.articles.index'));

    foreach ($articles as $article) {
        expect($article->fresh()->published_at)->toBeNull();
    }
});

it('prevents non-admin users from bulk unpublishing articles', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $articles = Article::factory()->count(2)->published()->create();

    actingAs($user)
        ->post(route('dashboard.articles.bulk-unpublish'), [
            'ids' => $articles->pluck('id')->toArray(),
        ])
        ->assertForbidden();
});

it('can toggle publish status for a single article', function () {
    $admin = User::factory()->admin()->create();
    $draft = Article::factory()->draft()->create();

    actingAs($admin)
        ->post(route('dashboard.articles.toggle-publish', $draft))
        ->assertRedirect(route('dashboard.articles.index'));

    expect($draft->fresh()->published_at)->not->toBeNull();

    actingAs($admin)
        ->post(route('dashboard.articles.toggle-publish', $draft))
        ->assertRedirect(route('dashboard.articles.index'));

    expect($draft->fresh()->published_at)->toBeNull();
});

it('prevents non-admin users from toggling publish status', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $article = Article::factory()->create();

    actingAs($user)
        ->post(route('dashboard.articles.toggle-publish', $article))
        ->assertForbidden();
});
