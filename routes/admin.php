<?php

use App\Http\Controllers\Admin\ArticleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('articles', ArticleController::class)->except(['show']);
});
