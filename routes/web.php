<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\NewsSourceController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SubscriberController;
use App\Http\Controllers\TagController;
use App\Models\Article;
use App\Models\Tag;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Sitemap and robots (public, cacheable)
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', function () {
    $sitemapUrl = rtrim(config('app.url'), '/').'/sitemap.xml';

    return response("User-agent: *\nDisallow:\n\nSitemap: {$sitemapUrl}\n", 200, [
        'Content-Type' => 'text/plain',
    ]);
});

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

// Location routes (hierarchical: country → region → city)
Route::get('/crime/{country}/{region}/{city}', [LocationController::class, 'showCity'])
    ->name('location.city')
    ->where(['country' => '[a-z]{2}', 'region' => '[a-z]{2,3}', 'city' => '[a-z0-9\-]+']);

Route::get('/crime/{country}/{region}', [LocationController::class, 'showRegion'])
    ->name('location.region')
    ->where(['country' => '[a-z]{2}', 'region' => '[a-z]{2,3}']);

Route::get('/crime/{country}', [LocationController::class, 'showCountry'])
    ->name('location.country')
    ->where(['country' => '[a-z]{2}']);

// Newsletter subscription routes
Route::post('/subscribe', [SubscriberController::class, 'store'])->name('subscribe.store');
Route::get('/subscribe/verify/{subscriber}', [SubscriberController::class, 'verify'])
    ->name('subscribe.verify')
    ->middleware('signed');
Route::get('/subscribe/unsubscribe/{subscriber}', [SubscriberController::class, 'unsubscribe'])
    ->name('subscribe.unsubscribe')
    ->middleware('signed');

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

// Ingestion metrics (North Cloud pipeline: received / skipped / ingested)
Route::get('dashboard/ingestion-metrics', function () {
    $metrics = app(\App\Services\IngestionMetricsService::class);

    return response()->json($metrics->getStats());
})->middleware(['auth', 'verified'])->name('dashboard.ingestion-metrics');

// Admin article routes are now loaded from northcloud-laravel package
// Configured via config/northcloud.php 'admin' section

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
