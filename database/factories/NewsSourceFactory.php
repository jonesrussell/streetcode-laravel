<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NewsSource>
 */
class NewsSourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'url' => fake()->url(),
            'logo_url' => null,
            'description' => fake()->paragraph(),
            'credibility_score' => fake()->numberBetween(40, 95),
            'bias_rating' => fake()->randomElement(['left', 'center-left', 'center', 'center-right', 'right']),
            'factual_reporting_score' => fake()->numberBetween(50, 100),
            'ownership' => fake()->company(),
            'country' => 'CA',
            'is_active' => true,
        ];
    }
}
