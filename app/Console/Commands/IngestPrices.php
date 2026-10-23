<?php

namespace App\Console\Commands;

use App\Models\Source;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IngestPrices extends Command
{
    protected $signature = 'prices:ingest {--date= : Date to ingest (Y-m-d), defaults to today}';

    protected $description = 'Ingest daily hotel prices from the mock source feeds';

    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->toDateString()
            : today()->toDateString();

        // Most recent day we have prices for, before the day we are ingesting
        $lastDate = DB::table('price_snapshots')
            ->where('snapshot_date', '<', $date)
            ->max('snapshot_date');

        if (! $lastDate) {
            $this->error('No earlier price history found. Run the seeders first.');

            return self::FAILURE;
        }

        $activeSources = Source::where('is_active', true)->pluck('id')->flip();
        $previous = DB::table('price_snapshots')->where('snapshot_date', $lastDate)->get();
        $now = now();
        $rows = [];

        foreach ($previous as $row) {
            if (! isset($activeSources[$row->source_id])) {
                continue;
            }

            // Mock feed: price moves between -4% and +4% from the last known price
            $change = mt_rand(-40, 40) / 1000;
            $price = max(3000, round($row->price * (1 + $change) / 100) * 100);

            $rows[] = [
                'hotel_id'      => $row->hotel_id,
                'source_id'     => $row->source_id,
                'price'         => $price,
                'currency'      => $row->currency,
                'snapshot_date' => $date,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('price_snapshots')->upsert(
                $chunk,
                ['hotel_id', 'source_id', 'snapshot_date'],
                ['price', 'currency', 'updated_at']
            );
        }

        $message = 'Ingested '.count($rows)." prices for {$date}";
        $this->info($message);
        Log::info($message);

        return self::SUCCESS;
    }
}