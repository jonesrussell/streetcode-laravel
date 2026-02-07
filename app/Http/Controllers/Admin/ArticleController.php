<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use JonesRussell\NorthCloud\Http\Controllers\Admin\ArticleController as BaseArticleController;

class ArticleController extends BaseArticleController
{
    protected function indexQuery(): Builder
    {
        return parent::indexQuery()->with('author');
    }

    protected function afterStore(Model $article, Request $request): void
    {
        $article->update(['author_id' => $request->user()->id]);
    }
}
