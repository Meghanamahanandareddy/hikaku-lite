<?php

namespace App\Console\Commands;

use App\Http\Controllers\HotelController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QueryTimings extends Command
{
    protected $signature = 'perf:queries';

    protected $description = 'Show how long each SQL query of the hotel list takes';

    public function handle(): int
    {
        $controller = app(HotelController::class);

        // Warm-up run, not measured
        $controller->index(Request::create('/api/hotels', 'GET'));

        $log = [];

        DB::listen(function ($query) use (&$log) {
            $log[] = [$query->time, $query->sql];
        });

        $controller->index(Request::create('/api/hotels', 'GET'));

        $rows = [];

        foreach ($log as $i => [$time, $sql]) {
            $rows[] = [
                $i + 1,
                sprintf('%.1f ms', $time),
                mb_substr(preg_replace('/\s+/', ' ', $sql), 0, 95),
            ];
        }

        $this->table(['#', 'Time', 'Query (start)'], $rows);

        return self::SUCCESS;
    }
}