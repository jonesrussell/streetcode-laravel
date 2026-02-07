<?php

namespace App\Processing;

use App\Models\City;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use JonesRussell\NorthCloud\Contracts\ArticleModel;
use JonesRussell\NorthCloud\Contracts\ArticleProcessor;
use JonesRussell\NorthCloud\Services\ArticleIngestionService;

class CrimeArticleProcessor implements ArticleProcessor
{
    private const ALLOWED_CRIME_TYPES = [
        'violent_crime',
        'property_crime',
        'drug_crime',
        'organized_crime',
        'criminal_justice',
        'gang_violence',
    ];

    public function __construct(
        protected ArticleIngestionService $ingestionService,
    ) {}

    public function shouldProcess(array $data): bool
    {
        return true;
    }

    public function process(array $data, ?ArticleModel $article): ?Model
    {
        $crimeRelevance = $data['crime_relevance'] ?? '';
        if ($crimeRelevance !== 'core_street_crime') {
            Log::info('Skipping non-core-crime article', [
                'crime_relevance' => $crimeRelevance,
                'title' => $data['title'] ?? $data['og_title'] ?? 'unknown',
                'external_id' => $data['id'] ?? 'unknown',
            ]);

            return null;
        }

        $skipDedup = ! empty($data['_replay']);
        $article = $this->ingestionService->ingest($data, $skipDedup);

        if (! $article) {
            return null;
        }

        $this->linkCity($article, $data);
        $this->mergeMetadata($article, $data);
        $this->filterTags($article, $data);

        return $article;
    }

    protected function linkCity(Model $article, array $data): void
    {
        $citySlug = $data['location_city'] ?? null;
        $regionCode = $data['location_province'] ?? null;
        $countryName = $data['location_country'] ?? null;

        if (! $citySlug || ! $regionCode || ! $countryName || $countryName === 'unknown') {
            return;
        }

        try {
            $city = City::findOrCreateFromLocation($citySlug, $regionCode, $countryName);
            $city->increment('article_count');
            $article->update(['city_id' => $city->id]);
        } catch (\Exception $e) {
            Log::error('Failed to link city', [
                'city_slug' => $citySlug,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function mergeMetadata(Model $article, array $data): void
    {
        $existing = $article->metadata ?? [];
        $extra = [];

        $fields = [
            'confidence', 'is_crime_related', 'crime_relevance', 'content_type',
            'word_count', 'category', 'section',
            'og_title', 'og_description', 'og_url',
            'location_city', 'location_province', 'location_country', 'location_confidence',
        ];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $extra[$field] = $data[$field];
            }
        }

        if (isset($data['keywords']) && is_array($data['keywords'])) {
            $extra['keywords'] = $data['keywords'];
        }

        if (! empty($extra)) {
            $article->update(['metadata' => array_merge($existing, $extra)]);
        }
    }

    protected function filterTags(Model $article, array $data): void
    {
        $topics = $data['topics'] ?? [];
        $filtered = array_filter($topics, fn ($t) => in_array($t, self::ALLOWED_CRIME_TYPES, true));

        if (empty($filtered)) {
            $article->tags()->detach();

            return;
        }

        $tagIds = [];
        foreach ($filtered as $topic) {
            $slug = Str::slug($topic);
            $tag = Tag::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => Str::title(str_replace('-', ' ', $slug)),
                    'type' => 'crime_category',
                ]
            );
            $tagIds[$tag->id] = ['confidence' => null];
        }

        $article->tags()->sync($tagIds);
    }
}
