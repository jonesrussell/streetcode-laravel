<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use JonesRussell\NorthCloud\Models\Article as BaseArticle;

class Article extends BaseArticle
{
    protected static function booted(): void
    {
        static::creating(function (Article $article) {
            if (empty($article->slug) && ! empty($article->title)) {
                $slug = Str::slug($article->title);

                if ($slug === '' || ctype_digit($slug)) {
                    $slug = "article-{$slug}";
                }

                $original = $slug;
                $counter = 1;

                while (static::where('slug', $slug)->exists()) {
                    $slug = "{$original}-{$counter}";
                    $counter++;
                }

                $article->slug = $slug;
            }
        });
    }

    protected $fillable = [
        'news_source_id',
        'city_id',
        'author_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'url',
        'external_id',
        'image_url',
        'author',
        'status',
        'published_at',
        'crawled_at',
        'metadata',
        'view_count',
        'is_featured',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopeSearch(Builder $query, string $searchTerm): Builder
    {
        if (in_array($query->getConnection()->getDriverName(), ['mysql', 'mariadb'])) {
            return $query->whereFullText(['title', 'excerpt', 'content'], $searchTerm);
        }

        $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $searchTerm);

        return $query->where(function ($q) use ($escaped) {
            $q->where('title', 'LIKE', "%{$escaped}%")
                ->orWhere('excerpt', 'LIKE', "%{$escaped}%")
                ->orWhere('content', 'LIKE', "%{$escaped}%");
        });
    }

    public function scopeInCity(Builder $query, City $city): Builder
    {
        return $query->where('city_id', $city->id);
    }

    public function scopeInRegion(Builder $query, string $countryCode, string $regionCode): Builder
    {
        return $query->whereHas('city', fn (Builder $q) => $q->inRegion($countryCode, $regionCode));
    }

    public function scopeInCountry(Builder $query, string $countryCode): Builder
    {
        return $query->whereHas('city', fn (Builder $q) => $q->inCountry($countryCode));
    }

    protected static function newFactory()
    {
        return \Database\Factories\ArticleFactory::new();
    }
}
