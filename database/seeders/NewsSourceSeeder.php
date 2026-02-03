<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class NewsSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sources = [
            [
                'name' => 'CBC News',
                'slug' => 'cbc-news',
                'url' => 'https://www.cbc.ca',
                'description' => 'Canadian Broadcasting Corporation - Canada\'s national public broadcaster',
                'credibility_score' => 85,
                'bias_rating' => 'center-left',
                'factual_reporting_score' => 90,
                'ownership' => 'Crown Corporation',
                'country' => 'CA',
                'is_active' => true,
            ],
            [
                'name' => 'CTV News',
                'slug' => 'ctv-news',
                'url' => 'https://www.ctvnews.ca',
                'description' => 'CTV News - Canada\'s #1 news network',
                'credibility_score' => 82,
                'bias_rating' => 'center',
                'factual_reporting_score' => 88,
                'ownership' => 'Bell Media',
                'country' => 'CA',
                'is_active' => true,
            ],
            [
                'name' => 'Toronto Star',
                'slug' => 'toronto-star',
                'url' => 'https://www.thestar.com',
                'description' => 'Toronto Star - Canada\'s largest daily newspaper',
                'credibility_score' => 78,
                'bias_rating' => 'center-left',
                'factual_reporting_score' => 85,
                'ownership' => 'Torstar Corporation',
                'country' => 'CA',
                'is_active' => true,
            ],
            [
                'name' => 'Global News',
                'slug' => 'global-news',
                'url' => 'https://globalnews.ca',
                'description' => 'Global News - Breaking news and current affairs from Canada and around the world',
                'credibility_score' => 80,
                'bias_rating' => 'center',
                'factual_reporting_score' => 86,
                'ownership' => 'Corus Entertainment',
                'country' => 'CA',
                'is_active' => true,
            ],
        ];

        foreach ($sources as $source) {
            \App\Models\NewsSource::create($source);
        }
    }
}
