<?php

namespace App\Http\Controllers;

use App\Models\Source;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function index(): JsonResponse
    {
        $latestDate = DB::table('price_snapshots')->max('snapshot_date');
        $previousDate = DB::table('price_snapshots')
            ->where('snapshot_date', '<', $latestDate)
            ->max('snapshot_date');

        // Today's prices from active sources
        $today = DB::table('price_snapshots as ps')
            ->join('sources as s', 's.id', '=', 'ps.source_id')
            ->where('s.is_active', true)
            ->where('ps.snapshot_date', $latestDate)
            ->select('ps.hotel_id', 'ps.source_id', 'ps.price')
            ->get();

        // Yesterday's prices, keyed by hotel and source
        $yesterday = collect();
        if ($previousDate) {
            $yesterday = DB::table('price_snapshots')
                ->where('snapshot_date', $previousDate)
                ->select('hotel_id', 'source_id', 'price')
                ->get()
                ->mapWithKeys(fn ($r) => [$r->hotel_id.'-'.$r->source_id => (float) $r->price]);
        }

        // Cheapest row per hotel today
        $bestPerHotel = $today->groupBy('hotel_id')
            ->map(fn ($rows) => $rows->sortBy('price')->first());

        // Same rule as the green "price drop" badge on the hotel cards
        $drops = $bestPerHotel->filter(function ($row) use ($yesterday) {
            $previous = $yesterday->get($row->hotel_id.'-'.$row->source_id);

            if (! $previous) {
                return false;
            }

            return round(((float) $row->price - $previous) / $previous * 100, 1) <= -2;
        })->count();

        return response()->json([
            'hotels'     => $bestPerHotel->count(),
            'sources'    => Source::where('is_active', true)->count(),
            'average'    => (int) round($bestPerHotel->avg('price')),
            'drops'      => $drops,
            'price_date' => $latestDate,
        ]);
    }
}