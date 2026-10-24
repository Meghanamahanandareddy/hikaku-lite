<?php

namespace App\Console\Commands;

use App\Http\Controllers\HotelController;
use App\Http\Controllers\StatsController;
use App\Models\Hotel;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class Benchmark extends Command
{
    protected $signature = 'perf:bench
                            {--runs=10 : Timed runs per scenario}
                            {--label= : Name for this run (for example before or after-index). Results are saved to storage/perf-results.txt}';

    protected $description = 'Measure how long the main API requests take';

    private int $queryCount = 0;
    private float $queryMs = 0.0;

    public function handle(): int
    {
        $runs = max(3, min(100, (int) $this->option('runs')));

        DB::listen(function ($query) {
            $this->queryCount++;
            $this->queryMs += $query->time;
        });

        $hotels = app(HotelController::class);
        $stats = app(StatsController::class);

        $demoId = Hotel::where('description', 'not like', '[load-test]%')->orderBy('id')->value('id');
        $bulkId = Hotel::where('description', 'like', '[load-test]%')->orderBy('id')->value('id') ?? $demoId;

        $scenarios = [
            'Hotel list (default sort)'  => fn () => $hotels->index(Request::create('/api/hotels', 'GET')),
            'Hotel list, Tokyo 4-star'   => fn () => $hotels->index(Request::create('/api/hotels', 'GET', ['city' => 'Tokyo', 'stars' => 4])),
            'Hotel list, name search'    => fn () => $hotels->index(Request::create('/api/hotels', 'GET', ['q' => 'grand'])),
            'Hotel list, sort by rating' => fn () => $hotels->index(Request::create('/api/hotels', 'GET', ['sort' => 'rating'])),
            'Hotel detail + chart data'  => fn () => $hotels->show(Hotel::findOrFail($bulkId)),
            'Dashboard stats'            => fn () => $stats->index(),
        ];

        $rowCount = DB::table('price_snapshots')->count();
        $version = DB::selectOne('select version() as v')->v;

        $this->info('Rows in price_snapshots: '.number_format($rowCount).' | MySQL '.$version.' | '.$runs.' timed runs each');
        $this->newLine();

        $rows = [];
        $report = [];

        foreach ($scenarios as $name => $run) {
            $run(); // warm-up run, not counted

            $times = [];
            $dbTimes = [];
            $queries = 0;

            for ($i = 0; $i < $runs; $i++) {
                $this->queryCount = 0;
                $this->queryMs = 0.0;

                $start = hrtime(true);
                $run();
                $times[] = (hrtime(true) - $start) / 1e6;

                $dbTimes[] = $this->queryMs;
                $queries = $this->queryCount;
            }

            sort($times);
            sort($dbTimes);

            $median = $this->median($times);
            $dbMedian = $this->median($dbTimes);

            $rows[] = [
                $name,
                sprintf('%.1f ms', $median),
                sprintf('%.1f ms', $times[0]),
                sprintf('%.1f ms', end($times)),
                $queries,
                sprintf('%.1f ms', $dbMedian),
            ];

            $report[] = sprintf('%-28s median %8.1f ms | fastest %8.1f | slowest %8.1f | %d queries | db %8.1f ms',
                $name, $median, $times[0], end($times), $queries, $dbMedian);
        }

        $this->table(['Scenario', 'Median', 'Fastest', 'Slowest', 'Queries', 'DB time (median)'], $rows);

        $label = $this->option('label');

        if ($label) {
            $block = "=== {$label} | ".now()->format('Y-m-d H:i')." | ".number_format($rowCount)." rows | MySQL {$version} ===\n"
                .implode("\n", $report)."\n\n";

            File::append(storage_path('perf-results.txt'), $block);
            $this->info('Saved to storage/perf-results.txt');
        }

        return self::SUCCESS;
    }

    private function median(array $sorted): float
    {
        $count = count($sorted);
        $middle = intdiv($count, 2);

        return $count % 2 ? $sorted[$middle] : ($sorted[$middle - 1] + $sorted[$middle]) / 2;
    }
}