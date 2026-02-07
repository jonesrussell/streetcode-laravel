<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence();

        return [
            'news_source_id' => \App\Models\NewsSource::factory(),
            'title' => $title,
            'slug' => \Illuminate\Support\Str::slug($title).'-'.fake()->unique()->randomNumber(5),
            'excerpt' => fake()->paragraph(),
            'content' => fake()->paragraphs(5, true),
            'url' => fake()->url(),
            'external_id' => fake()->uuid(),
            'image_url' => 'https://picsum.photos/800/400?random='.fake()->numberBetween(1, 1000),
            'author' => fake()->name(),
            'status' => 'published',
            'published_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'crawled_at' => now(),
            'view_count' => fake()->numberBetween(0, 1000),
            'is_featured' => fake()->boolean(10),
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function inCity(\App\Models\City $city): static
    {
        return $this->state(fn () => [
            'city_id' => $city->id,
        ]);
    }
}
