<?php

namespace Database\Seeders;

use App\Models\Source;
use Illuminate\Database\Seeder;

class SourceSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            ['name' => 'StayDeal',  'slug' => 'staydeal',  'website_url' => 'https://staydeal.example.com'],
            ['name' => 'RoomHub',   'slug' => 'roomhub',   'website_url' => 'https://roomhub.example.com'],
            ['name' => 'TripNest',  'slug' => 'tripnest',  'website_url' => 'https://tripnest.example.com'],
            ['name' => 'BookEasy',  'slug' => 'bookeasy',  'website_url' => 'https://bookeasy.example.com'],
        ];

        foreach ($sources as $source) {
            Source::updateOrCreate(
                ['slug' => $source['slug']],
                $source + ['is_active' => true]
            );
        }
    }
}