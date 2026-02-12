<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\City;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $baseUrl = rtrim(config('app.url'), '/');
        $urls = [];

        $urls[] = [
            'loc' => $baseUrl.'/',
            'changefreq' => 'hourly',
            'priority' => '1.0',
        ];

        Article::query()
            ->published()
            ->orderByDesc('published_at')
            ->select(['id', 'updated_at'])
            ->chunk(1000, function ($articles) use ($baseUrl, &$urls) {
                foreach ($articles as $article) {
                    $urls[] = [
                        'loc' => $baseUrl.route('articles.show', $article, false),
                        'lastmod' => $article->updated_at?->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.8',
                    ];
                }
            });

        $countries = array_keys(config('locations.countries', []));
        foreach ($countries as $country) {
            $urls[] = [
                'loc' => $baseUrl.route('location.country', ['country' => $country], false),
                'changefreq' => 'daily',
                'priority' => '0.7',
            ];
        }

        $regions = config('locations.regions', []);
        foreach ($regions as $country => $regionList) {
            foreach (array_keys($regionList) as $regionCode) {
                $region = strtolower($regionCode);
                $urls[] = [
                    'loc' => $baseUrl.route('location.region', ['country' => $country, 'region' => $region], false),
                    'changefreq' => 'daily',
                    'priority' => '0.6',
                ];
            }
        }

        City::query()
            ->withArticles()
            ->select(['country_code', 'region_code', 'city_slug', 'updated_at'])
            ->chunk(500, function ($cities) use ($baseUrl, &$urls) {
                foreach ($cities as $city) {
                    $urls[] = [
                        'loc' => $baseUrl.route('location.city', [
                            'country' => $city->country_code,
                            'region' => strtolower($city->region_code),
                            'city' => $city->city_slug,
                        ], false),
                        'lastmod' => $city->updated_at?->toAtomString(),
                        'changefreq' => 'daily',
                        'priority' => '0.6',
                    ];
                }
            });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $xml .= '  <url>'."\n";
            $xml .= '    <loc>'.e($u['loc']).'</loc>'."\n";
            if (! empty($u['lastmod'])) {
                $xml .= '    <lastmod>'.e($u['lastmod']).'</lastmod>'."\n";
            }
            if (! empty($u['changefreq'])) {
                $xml .= '    <changefreq>'.e($u['changefreq']).'</changefreq>'."\n";
            }
            if (isset($u['priority'])) {
                $xml .= '    <priority>'.e((string) $u['priority']).'</priority>'."\n";
            }
            $xml .= '  </url>'."\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
