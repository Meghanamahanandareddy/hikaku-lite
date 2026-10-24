<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LoadTestData extends Command
{
    // Every generated hotel starts its description with this, so it can be found and removed
    private const MARK = '[load-test]';

    protected $signature = 'perf:load
                            {--hotels=2500 : Number of extra hotels to create}
                            {--clear : Remove the load-test data instead}';

    protected $description = 'Create (or remove) a large amount of fake data for performance testing';

    public function handle(): int
    {
        DB::disableQueryLog();

        if ($this->option('clear')) {
            return $this->clear();
        }

        return $this->load((int) $this->option('hotels'));
    }

    private function clear(): int
    {
        $ids = DB::table('hotels')
            ->where('description', 'like', self::MARK.'%')
            ->pluck('id')
            ->all();

        if (! $ids) {
            $this->info('No load-test data found.');

            return self::SUCCESS;
        }

        $this->info('Removing '.count($ids).' load-test hotels and their prices...');

        foreach (array_chunk($ids, 100) as $chunk) {
            DB::table('price_snapshots')->whereIn('hotel_id', $chunk)->delete();
        }

        DB::table('hotels')->whereIn('id', $ids)->delete(); // alerts for them are removed too

        $this->info('Done.');

        return self::SUCCESS;
    }

    private function load(int $count): int
    {
        if ($count < 1 || $count > 20000) {
            $this->error('Use a number of hotels between 1 and 20000.');

            return self::FAILURE;
        }

        $existing = DB::table('hotels')->where('description', 'like', self::MARK.'%')->count();

        if ($existing > 0) {
            $this->error("Load-test data already exists ({$existing} hotels). Run: php artisan perf:load --clear");

            return self::FAILURE;
        }

        $sources = DB::table('sources')->where('is_active', true)->pluck('id')->all();
        $endDate = DB::table('price_snapshots')->max('snapshot_date');

        if (! $sources || ! $endDate) {
            $this->error('No sources or prices found. Run: php artisan migrate:fresh --seed');

            return self::FAILURE;
        }

        $started = microtime(true);

        // 90 days of dates, ending on the latest day already in the database
        $end = Carbon::parse($endDate)->startOfDay();
        $dates = [];
        $weekend = [];
        for ($i = 89; $i >= 0; $i--) {
            $day = $end->copy()->subDays($i);
            $dates[] = $day->toDateString();
            $weekend[] = in_array($day->dayOfWeekIso, [5, 6]) ? 1.15 : 1.0;
        }

        $cities = ['Tokyo', 'Kyoto', 'Osaka', 'Fukuoka', 'Sapporo', 'Nagoya', 'Yokohama', 'Kobe', 'Hiroshima', 'Sendai'];
        $areas = ['Central', 'Station', 'Riverside', 'Garden', 'Harbor', 'Old Town', 'Castle', 'Park', 'Bay', 'Hillside'];
        $prefixes = ['Grand', 'Royal', 'Sakura', 'Fuji', 'Urban', 'Zen', 'Maple', 'Blue', 'Golden', 'Quiet'];
        $types = ['Hotel', 'Inn', 'Resort', 'Lodge', 'Suites', 'Stay'];
        $starPool = [2, 3, 3, 3, 4, 4, 5];

        mt_srand(2026); // same fake data on every run

        // 1) Hotels
        $this->info("Creating {$count} hotels...");
        $now = now();
        $rows = [];

        for ($n = 1; $n <= $count; $n++) {
            $city = $cities[mt_rand(0, count($cities) - 1)];
            $area = $areas[mt_rand(0, count($areas) - 1)];
            $stars = $starPool[mt_rand(0, count($starPool) - 1)];

            $rows[] = [
                'name'         => $prefixes[mt_rand(0, count($prefixes) - 1)].' '.$area.' '
                                  .$types[mt_rand(0, count($types) - 1)].' '.$n,
                'city'         => $city,
                'area'         => $area,
                'star_rating'  => $stars,
                'review_score' => mt_rand(65, 96) / 10,
                'description'  => self::MARK." {$stars}-star hotel in {$area}, {$city}. Generated for performance testing.",
                'created_at'   => $now,
                'updated_at'   => $now,
            ];

            if (count($rows) >= 500) {
                DB::table('hotels')->insert($rows);
                $rows = [];
            }
        }

        if ($rows) {
            DB::table('hotels')->insert($rows);
        }

        // 2) Prices: hotels x sources x 90 days
        $hotels = DB::table('hotels')
            ->where('description', 'like', self::MARK.'%')
            ->get(['id', 'star_rating']);

        $this->info('Creating prices ('.number_format($hotels->count() * count($sources) * 90).' rows). This takes a minute or two...');

        $base = [2 => 8000, 3 => 13000, 4 => 22000, 5 => 45000];
        $bar = $this->output->createProgressBar($hotels->count());
        $bar->start();

        $buffer = [];
        $total = 0;

        foreach ($hotels as $hotel) {
            $level = $base[$hotel->star_rating] * (mt_rand(90, 115) / 100);
            $phase = mt_rand(0, 628) / 100;

            foreach ($sources as $sourceId) {
                $factor = mt_rand(94, 108) / 100;

                foreach ($dates as $index => $date) {
                    $wave = 1 + 0.08 * sin($index / 9 + $phase);
                    $noise = mt_rand(97, 103) / 100;
                    $price = round($level * $factor * $wave * $weekend[$index] * $noise / 100) * 100;

                    $buffer[] = [
                        'hotel_id'      => $hotel->id,
                        'source_id'     => $sourceId,
                        'price'         => $price,
                        'currency'      => 'JPY',
                        'snapshot_date' => $date,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                }
            }

            if (count($buffer) >= 2000) {
                DB::table('price_snapshots')->insert($buffer);
                $total += count($buffer);
                $buffer = [];
            }

            $bar->advance();
        }

        if ($buffer) {
            DB::table('price_snapshots')->insert($buffer);
            $total += count($buffer);
        }

        $bar->finish();
        $this->newLine(2);
        $this->info(sprintf(
            'Created %s hotels and %s price rows in %.1f seconds.',
            number_format($hotels->count()),
            number_format($total),
            microtime(true) - $started
        ));
        $this->line('Remove it later with: php artisan perf:load --clear');

        return self::SUCCESS;
    }
}