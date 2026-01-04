<?php

use App\Http\Controllers\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\NewsSourceController;
use App\Http\Controllers\TagController;
use App\Models\Article;
use App\Models\Tag;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public article routes - no authentication required
Route::get('/', [ArticleController::class, 'index'])->name('home');
Route::get('/articles/{article:id}', [ArticleController::class, 'show'])->name('articles.show');

// Tag routes
Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
Route::get('/tags/{tag:slug}', [TagController::class, 'show'])->name('tags.show');

// Source routes
Route::get('/sources', [NewsSourceController::class, 'index'])->name('sources.index');
Route::get('/sources/{newsSource:slug}', [NewsSourceController::class, 'show'])->name('sources.show');

// Search (uses ArticleController@index with search param)
Route::get('/search', [ArticleController::class, 'index'])->name('search');

// Authenticated dashboard
Route::get('dashboard', function (\Illuminate\Http\Request $request) {
    $articles = Article::query()
        ->with(['newsSource', 'tags'])
        ->published()
        ->when($request->tag, fn ($q) => $q->withTag($request->tag))
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

    $stats = [
        'total' => Article::count(),
        'published' => Article::whereNotNull('published_at')->count(),
        'drafts' => Article::whereNull('published_at')->count(),
        'featured' => Article::where('is_featured', true)->count(),
        'recent' => Article::where('created_at', '>=', now()->subDays(7))->count(),
        'total_views' => Article::sum('view_count'),
    ];

    return Inertia::render('Dashboard', [
        'articles' => $articles,
        'featuredArticles' => $featuredArticles,
        'popularTags' => $popularTags,
        'stats' => $stats,
        'filters' => [
            'tag' => $request->tag,
        ],
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

// Dashboard article management routes
Route::middleware(['auth', 'admin'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::resource('articles', AdminArticleController::class)->except(['show']);
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
