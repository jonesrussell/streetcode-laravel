<?php

return [
    'migrations' => [
        'enabled' => false,
    ],

    'redis' => [
        'connection' => env('NORTHCLOUD_REDIS_CONNECTION', 'northcloud'),
        'channels' => array_filter(array_map(
            'trim',
            explode(',', env('NORTHCLOUD_CHANNELS', implode(',', [
                // Layer 1: Crime topic channels (content:{topic} from publisher)
                'content:crime',
                'content:violent_crime',
                'content:criminal_justice',
                'content:drug_crime',
                'content:property_crime',
                'content:organized_crime',
                // Layer 2: DB channel (articles:crime from publisher)
                'articles:crime',
                // Layer 3: Crime classification channels
                'crime:homepage',
                'crime:category:violent-crime',
                'crime:category:property-crime',
                'crime:category:drug-crime',
                'crime:category:gang-violence',
                'crime:category:organized-crime',
                'crime:courts',
                'crime:context',
                'crime:category:crime',
                // Layer 4: Location channels (Canadian provinces)
                'crime:canada',
                'crime:province:on',
                'crime:province:bc',
                'crime:province:ab',
                'crime:province:qc',
                'crime:province:mb',
                'crime:province:sk',
                'crime:province:ns',
                'crime:province:nb',
                'crime:province:nl',
                'crime:province:pe',
                'crime:province:nt',
                'crime:province:nu',
                'crime:province:yt',
            ])))
        )),
    ],

    'quality' => [
        'min_score' => (int) env('NORTHCLOUD_MIN_QUALITY_SCORE', 0),
        'enabled' => (bool) env('NORTHCLOUD_QUALITY_FILTER', false),
    ],

    'models' => [
        'article' => \App\Models\Article::class,
        'news_source' => \App\Models\NewsSource::class,
        'tag' => \App\Models\Tag::class,
    ],

    'processors' => [
        \App\Processing\InstrumentedCrimeArticleProcessor::class,
    ],

    'processing' => [
        'sync' => (bool) env('NORTHCLOUD_PROCESS_SYNC', true),
    ],

    'content' => [
        'allowed_tags' => ['p', 'br', 'a', 'strong', 'em', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
    ],

    'tags' => [
        'default_type' => 'crime_category',
        'auto_create' => true,
        'allowed' => [],
    ],

    'navigation' => [
        'enabled' => true,
        'items' => [
            [
                'title' => 'Articles',
                'route' => 'dashboard.articles.index',
                'icon' => 'FileText',
            ],
        ],
    ],

    'admin' => [
        'resource' => \App\Admin\ArticleResource::class,
        'controller' => \App\Http\Controllers\Admin\ArticleController::class,
    ],
];
