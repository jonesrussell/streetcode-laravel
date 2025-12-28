<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Seed tags and news sources
        $this->call([
            TagSeeder::class,
            NewsSourceSeeder::class,
        ]);

        // Create sample articles for development
        if (app()->environment('local')) {
            $sources = \App\Models\NewsSource::all();
            $tags = \App\Models\Tag::type('crime_category')->get();

            \App\Models\Article::factory()
                ->count(50)
                ->create()
                ->each(function ($article) use ($tags) {
                    $article->tags()->attach(
                        $tags->random(rand(1, 3))->pluck('id')
                    );
                });
        }
    }
}
