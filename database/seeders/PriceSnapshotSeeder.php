<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\Source;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PriceSnapshotSeeder extends Seeder
{
    private const DAYS = 90;

    // Base nightly price in JPY by star rating
    private const BASE_PRICE = [
        2 => 8000,
        3 => 13000,
        4 => 22000,
        5 => 45000,
    ];

    public function run(): void
    {
        mt_srand(42); // same fake data on every run

        $hotels  = Hotel::all();
        $sources = Source::where('is_active', true)->get();
        $now     = now();
        $rows    = [];

        foreach ($hotels as $hotel) {
            $base  = self::BASE_PRICE[$hotel->star_rating] * (mt_rand(90, 115) / 100);
            $phase = mt_rand(0, 628) / 100;

            foreach ($sources as $source) {
                $sourceFactor = mt_rand(94, 108) / 100;

                for ($i = self::DAYS - 1; $i >= 0; $i--) {
                    $date     = now()->subDays($i);
                    $dayIndex = self::DAYS - 1 - $i;

                    $wave    = 1 + 0.08 * sin($dayIndex / 9 + $phase);
                    $weekend = in_array($date->dayOfWeekIso, [5, 6]) ? 1.15 : 1.0;
                    $noise   = mt_rand(97, 103) / 100;

                    // Recent price drop for every 4th hotel (for the alert demo)
                    $drop = ($hotel->id % 4 === 0 && $i < 3) ? 0.88 : 1.0;

                    $price = round($base * $sourceFactor * $wave * $weekend * $noise * $drop / 100) * 100;

                    $rows[] = [
                        'hotel_id'      => $hotel->id,
                        'source_id'     => $source->id,
                        'price'         => $price,
                        'currency'      => 'JPY',
                        'snapshot_date' => $date->toDateString(),
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('price_snapshots')->upsert(
                $chunk,
                ['hotel_id', 'source_id', 'snapshot_date'],
                ['price', 'currency', 'updated_at']
            );
        }
    }
}