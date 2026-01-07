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
        $heroArticle = Article::query()
            ->with(['newsSource', 'tags'])
            ->featured()
            ->published()
            ->whereNotNull('image_url')
            ->first();

        if (! $heroArticle) {
            $heroArticle = Article::query()
                ->with(['newsSource', 'tags'])
                ->published()
                ->whereNotNull('image_url')
                ->first();
        }

        $heroArticleId = $heroArticle?->id;

        $featuredArticles = Article::query()
            ->with(['newsSource', 'tags'])
            ->published()
            ->whereNotNull('image_url')
            ->when($heroArticleId, fn ($q) => $q->where('id', '!=', $heroArticleId))
            ->limit(3)
            ->get();

        $excludeIds = collect([$heroArticleId])
            ->merge($featuredArticles->pluck('id'))
            ->filter()
            ->toArray();

        $topStories = Article::query()
            ->with(['newsSource', 'tags'])
            ->published()
            ->whereNotIn('id', $excludeIds)
            ->limit(8)
            ->get();

        $excludeIds = array_merge($excludeIds, $topStories->pluck('id')->toArray());

        $crimeCategories = ['gang-violence', 'organized-crime', 'drug-crime', 'theft', 'assault'];
        $articlesByCategory = [];

        foreach ($crimeCategories as $categorySlug) {
            $tag = Tag::query()->where('slug', $categorySlug)->first();
            if ($tag) {
                $categoryArticles = Article::query()
                    ->with(['newsSource', 'tags'])
                    ->published()
                    ->whereNotIn('id', $excludeIds)
                    ->whereHas('tags', fn ($q) => $q->where('slug', $categorySlug))
                    ->limit(4)
                    ->get();

                if ($categoryArticles->isNotEmpty()) {
                    $articlesByCategory[] = [
                        'tag' => $tag,
                        'articles' => $categoryArticles,
                    ];
                    $excludeIds = array_merge($excludeIds, $categoryArticles->pluck('id')->toArray());
                }
            }
        }

        $articles = Article::query()
            ->with(['newsSource', 'tags'])
            ->published()
            ->when($request->tag, fn ($q) => $q->withTag($request->tag))
            ->when($request->search, fn ($q) => $q->search($request->search))
            ->when($request->source, fn ($q) => $q->where('news_source_id', $request->source))
            ->paginate(20)
            ->withQueryString();

        $popularTags = Tag::query()
            ->type('crime_category')
            ->popular()
            ->get();

        $trendingTopics = Tag::query()
            ->popular(15)
            ->get();

        return Inertia::render('Articles/Index', [
            'heroArticle' => $heroArticle,
            'featuredArticles' => $featuredArticles,
            'topStories' => $topStories,
            'articlesByCategory' => $articlesByCategory,
            'articles' => $articles,
            'popularTags' => $popularTags,
            'trendingTopics' => $trendingTopics,
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
