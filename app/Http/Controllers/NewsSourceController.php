<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\NewsSource;
use Inertia\Inertia;
use Inertia\Response;

class NewsSourceController extends Controller
{
    public function index(): Response
    {
        $sources = NewsSource::query()
            ->active()
            ->withCount('articles')
            ->orderBy('name')
            ->get();

        return Inertia::render('Sources/Index', [
            'sources' => $sources,
        ]);
    }

    public function show(NewsSource $newsSource): Response
    {
        $articles = Article::query()
            ->with(['tags'])
            ->published()
            ->where('news_source_id', $newsSource->id)
            ->paginate(20);

        return Inertia::render('Sources/Show', [
            'source' => $newsSource,
            'articles' => $articles,
        ]);
    }
}
