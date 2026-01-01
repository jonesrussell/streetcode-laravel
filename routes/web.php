<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\NewsSourceController;
use App\Http\Controllers\TagController;
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
Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
