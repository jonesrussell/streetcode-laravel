<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsSource extends Model
{
    /** @use HasFactory<\Database\Factories\NewsSourceFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'url',
        'logo_url',
        'description',
        'credibility_score',
        'bias_rating',
        'factual_reporting_score',
        'ownership',
        'country',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    protected function biasColor(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->bias_rating) {
                'left' => 'blue',
                'center-left' => 'sky',
                'center' => 'gray',
                'center-right' => 'orange',
                'right' => 'red',
                default => 'gray',
            },
        );
    }
}
