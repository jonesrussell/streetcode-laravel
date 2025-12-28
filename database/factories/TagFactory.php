<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tag>
 */
class TagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'type' => fake()->randomElement(['crime_category', 'location', 'topic']),
            'color' => fake()->randomElement(['red', 'blue', 'green', 'yellow', 'purple', 'orange']),
            'description' => fake()->sentence(),
            'article_count' => 0,
        ];
    }

    public function crimeCategory(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'crime_category',
        ]);
    }
}
