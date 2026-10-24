<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HotelController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q'         => 'nullable|string|max:100',
            'city'      => 'nullable|string|max:100',
            'stars'     => 'nullable|integer|between:1,5',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'min_score' => 'nullable|numeric|between:0,10',
            'sort'      => 'nullable|in:price_asc,price_desc,rating,stars,name',
            'per_page'  => 'nullable|integer|between:1,50',
        ]);

        // Most recent day we have prices for, and the day before it
        $latestDate = DB::table('price_snapshots')->max('snapshot_date');
        $previousDate = DB::table('price_snapshots')
            ->where('snapshot_date', '<', $latestDate)
            ->max('snapshot_date');

        // Cheapest price per hotel on the latest day (active sources only)
        $current = DB::table('price_snapshots as ps')
            ->join('sources as s', 's.id', '=', 'ps.source_id')
            ->where('s.is_active', true)
            ->where('ps.snapshot_date', $latestDate)
            ->select('ps.hotel_id', DB::raw('MIN(ps.price) as best_price'))
            ->groupBy('ps.hotel_id');

        $query = Hotel::query()
            ->joinSub($current, 'current_prices', 'current_prices.hotel_id', '=', 'hotels.id')
            ->select('hotels.*', 'current_prices.best_price');

        if (! empty($data['q'])) {
            $term = '%'.$data['q'].'%';
            $query->where(function ($q) use ($term) {
                $q->where('hotels.name', 'like', $term)
                  ->orWhere('hotels.area', 'like', $term);
            });
        }

        if (! empty($data['city'])) {
            $query->where('hotels.city', $data['city']);
        }

        if (! empty($data['stars'])) {
            $query->where('hotels.star_rating', $data['stars']);
        }

        if (isset($data['min_price'])) {
            $query->where('current_prices.best_price', '>=', $data['min_price']);
        }

        if (isset($data['max_price'])) {
            $query->where('current_prices.best_price', '<=', $data['max_price']);
        }

        if (isset($data['min_score'])) {
            $query->where('hotels.review_score', '>=', $data['min_score']);
        }

        match ($data['sort'] ?? 'price_asc') {
            'price_desc' => $query->orderByDesc('current_prices.best_price'),
            'rating'     => $query->orderByDesc('hotels.review_score'),
            'stars'      => $query->orderByDesc('hotels.star_rating'),
            'name'       => $query->orderBy('hotels.name'),
            default      => $query->orderBy('current_prices.best_price'),
        };

        $query->orderBy('hotels.id'); // keeps pagination order stable

        $paginator = $query
            ->paginate($data['per_page'] ?? 12)
            ->withQueryString();

        $hotelIds = $paginator->getCollection()->pluck('id');

        // Every source's price for the hotels on this page, today
        $todayRows = DB::table('price_snapshots as ps')
            ->join('sources as s', 's.id', '=', 'ps.source_id')
            ->whereIn('ps.hotel_id', $hotelIds)
            ->where('ps.snapshot_date', $latestDate)
            ->where('s.is_active', true)
            ->orderBy('ps.price')
            ->select('ps.hotel_id', 's.name as source', 's.slug', 'ps.price')
            ->get()
            ->groupBy('hotel_id');

        // The same prices yesterday, to show up/down changes
        $previousPrices = collect();
        if ($previousDate) {
            $previousPrices = DB::table('price_snapshots as ps')
                ->join('sources as s', 's.id', '=', 'ps.source_id')
                ->whereIn('ps.hotel_id', $hotelIds)
                ->where('ps.snapshot_date', $previousDate)
                ->select('ps.hotel_id', 's.slug', 'ps.price')
                ->get()
                ->mapWithKeys(fn ($row) => [$row->hotel_id.'-'.$row->slug => (float) $row->price]);
        }

        $paginator->getCollection()->each(function ($hotel) use ($todayRows, $previousPrices) {
            $rows = $todayRows->get($hotel->id, collect());

            $prices = $rows->values()->map(function ($row, $index) use ($hotel, $previousPrices) {
                $price = (float) $row->price;
                $previous = $previousPrices->get($hotel->id.'-'.$row->slug);

                return [
                    'source'     => $row->source,
                    'slug'       => $row->slug,
                    'price'      => $price,
                    'previous'   => $previous,
                    'change_pct' => $previous ? round((($price - $previous) / $previous) * 100, 1) : null,
                    'is_best'    => $index === 0, // rows are sorted cheapest first
                ];
            })->all();

            $hotel->setAttribute('prices', $prices);
        });

        return response()->json(
            array_merge($paginator->toArray(), [
                'price_date'      => $latestDate,
                'previous_date'   => $previousDate,
            ])
        );
    }
}