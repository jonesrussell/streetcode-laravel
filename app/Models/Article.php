<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    /** @use HasFactory<\Database\Factories\ArticleFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'news_source_id',
        'title',
        'excerpt',
        'content',
        'url',
        'external_id',
        'image_url',
        'author',
        'published_at',
        'crawled_at',
        'metadata',
        'view_count',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'crawled_at' => 'datetime',
            'metadata' => 'array',
            'is_featured' => 'boolean',
        ];
    }

    public function newsSource(): BelongsTo
    {
        return $this->belongsTo(NewsSource::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)
            ->withTimestamps()
            ->withPivot('confidence');
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('published_at', '<=', now())
            ->orderByDesc('published_at');
    }

    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    public function scopeWithTag(Builder $query, string $tagSlug): void
    {
        $query->whereHas('tags', function (Builder $q) use ($tagSlug) {
            $q->where('slug', $tagSlug);
        });
    }

    public function scopeSearch(Builder $query, string $searchTerm): void
    {
        $query->whereFullText(['title', 'excerpt', 'content'], $searchTerm);
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }
}
