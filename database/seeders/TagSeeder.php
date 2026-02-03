<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $crimeTags = [
            ['name' => 'Drug Crime', 'slug' => 'drug-crime', 'type' => 'crime_category', 'color' => 'purple', 'description' => 'Drug-related criminal activities'],
            ['name' => 'Street Crime', 'slug' => 'street-crime', 'type' => 'crime_category', 'color' => 'red', 'description' => 'Crimes occurring in public spaces'],
            ['name' => 'Organized Crime', 'slug' => 'organized-crime', 'type' => 'crime_category', 'color' => 'orange', 'description' => 'Coordinated criminal organizations'],
            ['name' => 'Gang Violence', 'slug' => 'gang-violence', 'type' => 'crime_category', 'color' => 'red', 'description' => 'Gang-related violent activities'],
            ['name' => 'Theft', 'slug' => 'theft', 'type' => 'crime_category', 'color' => 'yellow', 'description' => 'Property theft and robbery'],
            ['name' => 'Assault', 'slug' => 'assault', 'type' => 'crime_category', 'color' => 'red', 'description' => 'Physical assault and battery'],
            ['name' => 'Fraud', 'slug' => 'fraud', 'type' => 'crime_category', 'color' => 'orange', 'description' => 'Financial fraud and scams'],
        ];

        foreach ($crimeTags as $tag) {
            \App\Models\Tag::create($tag);
        }
    }
}
