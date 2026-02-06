<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class City extends Model
{
    /** @use HasFactory<\Database\Factories\CityFactory> */
    use HasFactory;

    protected $fillable = [
        'city_slug',
        'city_name',
        'region_code',
        'region_name',
        'country_code',
        'country_name',
        'article_count',
    ];

    protected $casts = [
        'article_count' => 'integer',
    ];

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function scopeInCountry(Builder $query, string $countryCode): void
    {
        $query->where('country_code', strtolower($countryCode));
    }

    public function scopeInRegion(Builder $query, string $countryCode, string $regionCode): void
    {
        $query->where('country_code', strtolower($countryCode))
            ->where('region_code', strtoupper($regionCode));
    }

    public function scopeWithArticles(Builder $query): void
    {
        $query->where('article_count', '>', 0);
    }

    public function scopePopular(Builder $query, int $limit = 10): void
    {
        $query->orderByDesc('article_count')->limit($limit);
    }

    public function getUrlPathAttribute(): string
    {
        return '/crime/'.strtolower($this->country_code).'/'.strtolower($this->region_code).'/'.$this->city_slug;
    }

    /**
     * Find or create a city from North Cloud location data.
     */
    public static function findOrCreateFromLocation(
        string $citySlug,
        string $regionCode,
        string $countryName
    ): self {
        $countryCode = config("locations.country_codes.{$countryName}", Str::substr($countryName, 0, 2));
        $regionCode = strtoupper($regionCode);

        return static::firstOrCreate(
            [
                'country_code' => $countryCode,
                'region_code' => $regionCode,
                'city_slug' => $citySlug,
            ],
            [
                'city_name' => Str::title(str_replace('-', ' ', $citySlug)),
                'region_name' => config("locations.regions.{$countryCode}.{$regionCode}", $regionCode),
                'country_name' => config("locations.countries.{$countryCode}", Str::title($countryName)),
            ]
        );
    }
}
