<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\Source;
use Illuminate\Support\Facades\DB;

class PartnerController extends Controller
{
    // Brand colour for each mock booking site
    private const BRANDS = [
        'staydeal' => '#ff6b35',
        'roomhub'  => '#2b6cb0',
        'tripnest' => '#1a8f4c',
        'bookeasy' => '#7b2cbf',
    ];

    public function show(Hotel $hotel, string $slug)
    {
        $source = Source::where('slug', $slug)->where('is_active', true)->firstOrFail();

        // Latest price this source has for this hotel
        $latest = DB::table('price_snapshots')
            ->where('hotel_id', $hotel->id)
            ->where('source_id', $source->id)
            ->orderByDesc('snapshot_date')
            ->first();

        abort_if(! $latest, 404);

        return view('partner', [
            'hotel'     => $hotel,
            'source'    => $source,
            'price'     => (float) $latest->price,
            'priceDate' => $latest->snapshot_date,
            'brand'     => self::BRANDS[$slug] ?? '#e61c5d',
            'photo'     => '/img/img_'.(($hotel->id % 5) + 1).'.jpg',
        ]);
    }
}