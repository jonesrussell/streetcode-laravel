<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ArticleController extends Controller
{
    public function index(Request $request): Response
    {
        $articles = Article::query()
            ->with(['newsSource', 'tags'])
            ->published()
            ->when($request->tag, fn ($q) => $q->withTag($request->tag))
            ->when($request->search, fn ($q) => $q->search($request->search))
            ->when($request->source, fn ($q) => $q->where('news_source_id', $request->source))
            ->paginate(20)
            ->withQueryString();

        $featuredArticles = Article::query()
            ->with(['newsSource', 'tags'])
            ->featured()
            ->published()
            ->limit(3)
            ->get();

        $popularTags = Tag::query()
            ->type('crime_category')
            ->popular()
            ->get();

        return Inertia::render('Articles/Index', [
            'articles' => $articles,
            'featuredArticles' => $featuredArticles,
            'popularTags' => $popularTags,
            'filters' => [
                'tag' => $request->tag,
                'search' => $request->search,
                'source' => $request->source,
            ],
        ]);
    }

    public function show(Article $article): Response
    {
        $article->load(['newsSource', 'tags']);
        $article->incrementViewCount();

        $relatedArticles = Article::query()
            ->with(['newsSource', 'tags'])
            ->published()
            ->where('id', '!=', $article->id)
            ->where(function ($query) use ($article) {
                $query->whereHas('tags', function ($q) use ($article) {
                    $q->whereIn('tags.id', $article->tags->pluck('id'));
                })->orWhere('news_source_id', $article->news_source_id);
            })
            ->limit(6)
            ->get();

        return Inertia::render('Articles/Show', [
            'article' => $article,
            'relatedArticles' => $relatedArticles,
        ]);
    }
}
