<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    /**
     * Run the database seeds.
     *
     * These 5 crime sub-categories match North Cloud's classifier output:
     * - violent_crime, property_crime, drug_crime, organized_crime, criminal_justice
     */
    public function run(): void
    {
        $crimeTags = [
            ['name' => 'Violent Crime', 'slug' => 'violent-crime', 'type' => 'crime_category', 'color' => 'red', 'description' => 'Murder, assault, shootings, gang violence'],
            ['name' => 'Property Crime', 'slug' => 'property-crime', 'type' => 'crime_category', 'color' => 'yellow', 'description' => 'Theft, burglary, arson, vandalism'],
            ['name' => 'Drug Crime', 'slug' => 'drug-crime', 'type' => 'crime_category', 'color' => 'purple', 'description' => 'Drug trafficking, busts, possession'],
            ['name' => 'Organized Crime', 'slug' => 'organized-crime', 'type' => 'crime_category', 'color' => 'orange', 'description' => 'Cartels, crime syndicates, racketeering'],
            ['name' => 'Criminal Justice', 'slug' => 'criminal-justice', 'type' => 'crime_category', 'color' => 'blue', 'description' => 'Court cases, trials, arrests, sentencing'],
        ];

        foreach ($crimeTags as $tag) {
            \App\Models\Tag::updateOrCreate(
                ['slug' => $tag['slug']],
                $tag
            );
        }
    }
}
