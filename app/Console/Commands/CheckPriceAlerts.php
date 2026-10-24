<?php

namespace App\Console\Commands;

use App\Mail\PriceDropAlert;
use App\Models\PriceAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CheckPriceAlerts extends Command
{
    // Don't email the same alert again within this many days
    private const RENOTIFY_DAYS = 7;

    protected $signature = 'alerts:check {--dry-run : Show matches without sending any email}';

    protected $description = 'Email people whose price-drop target has been reached';

    public function handle(): int
    {
        $latestDate = DB::table('price_snapshots')->max('snapshot_date');

        if (! $latestDate) {
            $this->error('No prices found. Run the seeders or prices:ingest first.');

            return self::FAILURE;
        }

        $checked = 0;
        $matched = 0;
        $sent = 0;
        $dryRun = (bool) $this->option('dry-run');

        PriceAlert::with('hotel')
            ->where('is_active', true)
            ->chunkById(100, function ($alerts) use ($latestDate, $dryRun, &$checked, &$matched, &$sent) {
                // Cheapest source per hotel today, for the hotels in this batch
                $best = DB::table('price_snapshots as ps')
                    ->join('sources as s', 's.id', '=', 'ps.source_id')
                    ->where('s.is_active', true)
                    ->where('ps.snapshot_date', $latestDate)
                    ->whereIn('ps.hotel_id', $alerts->pluck('hotel_id')->unique())
                    ->orderBy('ps.price')
                    ->select('ps.hotel_id', 'ps.price', 's.name as source', 's.slug')
                    ->get()
                    ->groupBy('hotel_id')
                    ->map(fn ($rows) => $rows->first());

                foreach ($alerts as $alert) {
                    $checked++;
                    $row = $best->get($alert->hotel_id);

                    // No price today, or price still above the target
                    if (! $row || (float) $row->price > (float) $alert->target_price) {
                        continue;
                    }

                    // Already emailed recently
                    if ($alert->last_notified_at
                        && $alert->last_notified_at->gt(now()->subDays(self::RENOTIFY_DAYS))) {
                        continue;
                    }

                    $matched++;
                    $this->line(sprintf(
                        'MATCH: %s - %s at %s (target %s)',
                        $alert->email,
                        $alert->hotel->name,
                        number_format($row->price),
                        number_format($alert->target_price)
                    ));

                    if ($dryRun) {
                        continue;
                    }

                    try {
                        Mail::to($alert->email)->send(new PriceDropAlert(
                            $alert,
                            $alert->hotel,
                            (float) $row->price,
                            $row->source,
                            url("/go/{$alert->hotel_id}/{$row->slug}")
                        ));

                        $alert->forceFill(['last_notified_at' => now()])->save();
                        $sent++;
                    } catch (\Throwable $e) {
                        Log::error('Price alert email failed', [
                            'alert_id' => $alert->id,
                            'error'    => $e->getMessage(),
                        ]);
                        $this->error("Failed to email {$alert->email}: {$e->getMessage()}");
                    }
                }
            });

        $summary = "Checked {$checked} alerts, {$matched} matched, {$sent} emails sent"
            .($dryRun ? ' (dry run, nothing sent)' : '');

        $this->info($summary);
        Log::info($summary);

        return self::SUCCESS;
    }
}