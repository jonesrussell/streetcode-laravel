<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\City>
 */
class CityFactory extends Factory
{
    public function definition(): array
    {
        $cityName = fake()->city();
        $regionCode = fake()->randomElement(['ON', 'BC', 'QC', 'AB', 'MB', 'NS']);

        return [
            'city_slug' => Str::slug($cityName),
            'city_name' => $cityName,
            'region_code' => $regionCode,
            'region_name' => config("locations.regions.ca.{$regionCode}", $regionCode),
            'country_code' => 'ca',
            'country_name' => 'Canada',
            'article_count' => fake()->numberBetween(0, 50),
        ];
    }

    public function inOntario(): static
    {
        return $this->state(fn () => [
            'region_code' => 'ON',
            'region_name' => 'Ontario',
        ]);
    }

    public function withArticles(int $count = 10): static
    {
        return $this->state(fn () => [
            'article_count' => $count,
        ]);
    }
}
