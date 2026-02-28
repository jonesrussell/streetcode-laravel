<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Tag;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    public function index(): Response
    {
        $crimeCategories = Tag::query()
            ->type('crime_category')
            ->withCount('articles')
            ->orderByDesc('article_count')
            ->get();

        return Inertia::render('Tags/Index', [
            'crimeCategories' => $crimeCategories,
        ]);
    }

    public function show(Tag $tag): Response
    {
        $articles = Article::query()
            ->with(['newsSource', 'tags'])
            ->published()
            ->withTag($tag->slug)
            ->latest('published_at')
            ->paginate(20);

        return Inertia::render('Tags/Show', [
            'tag' => $tag,
            'articles' => $articles,
        ]);
    }
}
