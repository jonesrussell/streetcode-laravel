<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\City;
use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class LocationController extends Controller
{
    public function showCountry(Request $request, string $countryCode): Response
    {
        $countryCode = strtolower($countryCode);
        $countryName = config("locations.countries.{$countryCode}");

        abort_unless($countryName, HttpResponse::HTTP_NOT_FOUND);

        $regions = City::query()
            ->inCountry($countryCode)
            ->withArticles()
            ->selectRaw('region_code, region_name, SUM(article_count) as total_articles, COUNT(*) as city_count')
            ->groupBy('region_code', 'region_name')
            ->orderByDesc('total_articles')
            ->get();

        $topCities = City::query()
            ->inCountry($countryCode)
            ->withArticles()
            ->popular(12)
            ->get();

        $articles = Article::query()
            ->with(['newsSource', 'tags', 'city'])
            ->published()
            ->inCountry($countryCode)
            ->when($request->tag, fn ($q) => $q->withTag($request->tag))
            ->paginate(20)
            ->withQueryString();

        $popularTags = Tag::query()
            ->type('crime_category')
            ->popular()
            ->get();

        return Inertia::render('Location/Country', [
            'location' => [
                'country' => $countryCode,
                'countryName' => $countryName,
                'level' => 'country',
            ],
            'regions' => $regions,
            'topCities' => $topCities,
            'articles' => $articles,
            'popularTags' => $popularTags,
            'filters' => [
                'tag' => $request->tag,
            ],
        ]);
    }

    public function showRegion(Request $request, string $countryCode, string $regionCode): Response
    {
        $countryCode = strtolower($countryCode);
        $regionCode = strtoupper($regionCode);

        $countryName = config("locations.countries.{$countryCode}");
        $regionName = config("locations.regions.{$countryCode}.{$regionCode}");

        abort_unless($countryName && $regionName, HttpResponse::HTTP_NOT_FOUND);

        $cities = City::query()
            ->inRegion($countryCode, $regionCode)
            ->withArticles()
            ->popular(30)
            ->get();

        $articles = Article::query()
            ->with(['newsSource', 'tags', 'city'])
            ->published()
            ->inRegion($countryCode, $regionCode)
            ->when($request->tag, fn ($q) => $q->withTag($request->tag))
            ->paginate(20)
            ->withQueryString();

        $popularTags = Tag::query()
            ->type('crime_category')
            ->popular()
            ->get();

        return Inertia::render('Location/Region', [
            'location' => [
                'country' => $countryCode,
                'countryName' => $countryName,
                'region' => strtolower($regionCode),
                'regionName' => $regionName,
                'level' => 'region',
            ],
            'cities' => $cities,
            'articles' => $articles,
            'popularTags' => $popularTags,
            'filters' => [
                'tag' => $request->tag,
            ],
        ]);
    }

    public function showCity(Request $request, string $countryCode, string $regionCode, string $citySlug): Response
    {
        $countryCode = strtolower($countryCode);
        $regionCode = strtoupper($regionCode);

        $city = City::query()
            ->where('country_code', $countryCode)
            ->where('region_code', $regionCode)
            ->where('city_slug', $citySlug)
            ->firstOrFail();

        $heroArticle = Article::query()
            ->with(['newsSource', 'tags'])
            ->published()
            ->inCity($city)
            ->whereNotNull('image_url')
            ->first();

        $heroArticleId = $heroArticle?->id;

        $featuredArticles = Article::query()
            ->with(['newsSource', 'tags'])
            ->published()
            ->inCity($city)
            ->whereNotNull('image_url')
            ->when($heroArticleId, fn ($q) => $q->where('id', '!=', $heroArticleId))
            ->limit(3)
            ->get();

        $excludeIds = collect([$heroArticleId])
            ->merge($featuredArticles->pluck('id'))
            ->filter()
            ->toArray();

        $topStories = Article::query()
            ->with(['newsSource', 'tags'])
            ->published()
            ->inCity($city)
            ->whereNotIn('id', $excludeIds)
            ->limit(8)
            ->get();

        $articles = Article::query()
            ->with(['newsSource', 'tags'])
            ->published()
            ->inCity($city)
            ->when($request->tag, fn ($q) => $q->withTag($request->tag))
            ->when($request->search, fn ($q) => $q->search($request->search))
            ->paginate(20)
            ->withQueryString();

        $popularTags = Tag::query()
            ->type('crime_category')
            ->popular()
            ->get();

        $nearbyCities = City::query()
            ->inRegion($countryCode, $regionCode)
            ->where('id', '!=', $city->id)
            ->withArticles()
            ->popular(8)
            ->get();

        return Inertia::render('Location/City', [
            'location' => [
                'country' => $countryCode,
                'countryName' => $city->country_name,
                'region' => strtolower($city->region_code),
                'regionName' => $city->region_name,
                'city' => $city->city_slug,
                'cityName' => $city->city_name,
                'level' => 'city',
            ],
            'city' => $city,
            'heroArticle' => $heroArticle,
            'featuredArticles' => $featuredArticles,
            'topStories' => $topStories,
            'articles' => $articles,
            'popularTags' => $popularTags,
            'nearbyCities' => $nearbyCities,
            'filters' => [
                'tag' => $request->tag,
                'search' => $request->search,
            ],
        ]);
    }
}
