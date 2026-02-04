<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreArticleRequest;
use App\Http\Requests\Admin\UpdateArticleRequest;
use App\Models\Article;
use App\Models\NewsSource;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ArticleController extends Controller
{
    public function index(Request $request): Response
    {
        $articles = Article::query()
            ->with(['newsSource', 'tags', 'author'])
            ->when($request->status, fn ($q) => $request->status === 'draft'
                    ? $q->whereNull('published_at')
                    : $q->whereNotNull('published_at')
            )
            ->when($request->search, fn ($q) => $q->search($request->search))
            ->when($request->tag, fn ($q) => $q->withTag($request->tag))
            ->when($request->source, fn ($q) => $q->where('news_source_id', $request->source))
            ->when($request->sort, fn ($q) => $q->orderBy($request->sort, $request->direction ?? 'desc'),
                fn ($q) => $q->latest('created_at')
            )
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('dashboard/articles/Index', [
            'articles' => $articles,
            'filters' => $request->only(['status', 'search', 'tag', 'source', 'sort', 'direction']),
            'stats' => [
                'total' => Article::count(),
                'drafts' => Article::whereNull('published_at')->count(),
                'published' => Article::whereNotNull('published_at')->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('dashboard/articles/Create', [
            'newsSources' => NewsSource::active()->orderBy('name')->get(),
            'tags' => Tag::orderBy('name')->get(),
        ]);
    }

    public function store(StoreArticleRequest $request): RedirectResponse
    {
        $article = Article::create([
            ...$request->validated(),
            'author_id' => $request->user()->id,
        ]);

        if ($request->has('tags')) {
            $article->tags()->sync($request->tags);
        }

        return to_route('dashboard.articles.index')->with('success', 'Article created successfully.');
    }

    public function show(Article $article): Response
    {
        $article->load(['newsSource', 'tags', 'author']);

        return Inertia::render('dashboard/articles/Show', [
            'article' => $article,
        ]);
    }

    public function edit(Article $article): Response
    {
        $article->load(['newsSource', 'tags', 'author']);

        return Inertia::render('dashboard/articles/Edit', [
            'article' => $article,
            'newsSources' => NewsSource::active()->orderBy('name')->get(),
            'tags' => Tag::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateArticleRequest $request, Article $article): RedirectResponse
    {
        $article->update($request->validated());

        if ($request->has('tags')) {
            $article->tags()->sync($request->tags);
        }

        return to_route('dashboard.articles.index')->with('success', 'Article updated successfully.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        $article->delete();

        return to_route('dashboard.articles.index')->with('success', 'Article deleted successfully.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:articles,id',
        ]);

        Article::whereIn('id', $request->ids)->delete();

        $count = count($request->ids);
        $message = $count === 1
            ? 'Article deleted successfully.'
            : "{$count} articles deleted successfully.";

        return to_route('dashboard.articles.index')->with('success', $message);
    }

    public function bulkPublish(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:articles,id',
        ]);

        $now = now();
        Article::whereIn('id', $request->ids)->update(['published_at' => $now]);

        $count = count($request->ids);
        $message = $count === 1
            ? 'Article published successfully.'
            : "{$count} articles published successfully.";

        return to_route('dashboard.articles.index')->with('success', $message);
    }

    public function bulkUnpublish(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:articles,id',
        ]);

        Article::whereIn('id', $request->ids)->update(['published_at' => null]);

        $count = count($request->ids);
        $message = $count === 1
            ? 'Article unpublished successfully.'
            : "{$count} articles unpublished successfully.";

        return to_route('dashboard.articles.index')->with('success', $message);
    }

    public function togglePublish(Article $article): RedirectResponse
    {
        if ($article->published_at) {
            $article->update(['published_at' => null]);
            $message = 'Article unpublished successfully.';
        } else {
            $article->update(['published_at' => now()]);
            $message = 'Article published successfully.';
        }

        return to_route('dashboard.articles.index')->with('success', $message);
    }

    public function trashed(Request $request): Response
    {
        $articles = Article::onlyTrashed()
            ->with(['newsSource', 'tags', 'author'])
            ->when($request->search, fn ($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->when($request->source, fn ($q) => $q->where('news_source_id', $request->source))
            ->when($request->channel, fn ($q) => $q->whereRaw(
                "JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.publisher.channel')) = ?",
                [$request->channel]
            ))
            ->when($request->sort, fn ($q) => $q->orderBy($request->sort, $request->direction ?? 'desc'),
                fn ($q) => $q->latest('deleted_at')
            )
            ->paginate(15)
            ->withQueryString();

        // Get distinct channels for filter dropdown
        $channels = Article::onlyTrashed()
            ->selectRaw("DISTINCT JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.publisher.channel')) as channel")
            ->whereNotNull('metadata')
            ->pluck('channel')
            ->filter()
            ->sort()
            ->values();

        return Inertia::render('dashboard/articles/Trashed', [
            'articles' => $articles,
            'filters' => $request->only(['search', 'source', 'channel', 'sort', 'direction']),
            'channels' => $channels,
            'newsSources' => NewsSource::orderBy('name')->get(['id', 'name']),
            'stats' => [
                'trashed' => Article::onlyTrashed()->count(),
                'active' => Article::count(),
            ],
        ]);
    }

    public function restore(int $id): RedirectResponse
    {
        $article = Article::onlyTrashed()->findOrFail($id);
        $article->restore();

        return to_route('dashboard.articles.trashed')->with('success', 'Article restored successfully.');
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $count = Article::onlyTrashed()->whereIn('id', $request->ids)->restore();

        $message = $count === 1
            ? 'Article restored successfully.'
            : "{$count} articles restored successfully.";

        return to_route('dashboard.articles.trashed')->with('success', $message);
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $article = Article::onlyTrashed()->findOrFail($id);
        $article->forceDelete();

        return to_route('dashboard.articles.trashed')->with('success', 'Article permanently deleted.');
    }

    public function bulkForceDelete(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $count = Article::onlyTrashed()->whereIn('id', $request->ids)->forceDelete();

        $message = $count === 1
            ? 'Article permanently deleted.'
            : "{$count} articles permanently deleted.";

        return to_route('dashboard.articles.trashed')->with('success', $message);
    }
}
