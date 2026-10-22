<?php

namespace Database\Seeders;

use App\Models\Hotel;
use Illuminate\Database\Seeder;

class HotelSeeder extends Seeder
{
    public function run(): void
    {
        $hotels = [
            // Tokyo
            ['Shibuya Skyline Hotel',     'Tokyo',   'Shibuya',   4, 8.6],
            ['Ebisu Garden Inn',          'Tokyo',   'Ebisu',     3, 8.1],
            ['Shinjuku Central Stay',     'Tokyo',   'Shinjuku',  4, 8.4],
            ['Asakusa Riverside Lodge',   'Tokyo',   'Asakusa',   3, 8.0],
            ['Ginza Grand Residence',     'Tokyo',   'Ginza',     5, 9.2],
            ['Ueno Park Business Hotel',  'Tokyo',   'Ueno',      2, 7.4],
            ['Roppongi Hills Suites',     'Tokyo',   'Roppongi',  5, 9.0],
            // Kyoto
            ['Gion Heritage Ryokan',      'Kyoto',   'Gion',      5, 9.4],
            ['Kyoto Station Comfort',     'Kyoto',   'Kyoto Station', 3, 7.9],
            ['Arashiyama Bamboo Inn',     'Kyoto',   'Arashiyama', 4, 8.8],
            ['Kawaramachi City Hotel',    'Kyoto',   'Kawaramachi', 3, 8.2],
            ['Higashiyama Temple Stay',   'Kyoto',   'Higashiyama', 4, 8.7],
            // Osaka
            ['Namba Neon Hotel',          'Osaka',   'Namba',     3, 8.0],
            ['Umeda Sky Tower Hotel',     'Osaka',   'Umeda',     4, 8.5],
            ['Dotonbori Canal Inn',       'Osaka',   'Dotonbori', 3, 7.8],
            ['Osaka Bay Resort',          'Osaka',   'Bay Area',  5, 9.1],
            ['Shinsaibashi Budget Stay',  'Osaka',   'Shinsaibashi', 2, 7.3],
            // Fukuoka
            ['Hakata Station Hotel',      'Fukuoka', 'Hakata',    3, 8.1],
            ['Tenjin Garden Hotel',       'Fukuoka', 'Tenjin',    4, 8.5],
            ['Nakasu River View Inn',     'Fukuoka', 'Nakasu',    2, 7.5],
        ];

        foreach ($hotels as [$name, $city, $area, $stars, $score]) {
            Hotel::updateOrCreate(
                ['name' => $name, 'city' => $city],
                [
                    'area'         => $area,
                    'star_rating'  => $stars,
                    'review_score' => $score,
                    'description'  => "{$stars}-star hotel in {$area}, {$city}. Mock data for the Hikaku Lite demo.",
                ]
            );
        }
    }
}