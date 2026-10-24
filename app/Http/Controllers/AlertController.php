<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\PriceAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlertController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'hotel_id'     => 'required|integer|exists:hotels,id',
            'email'        => 'required|email:rfc|max:255',
            'target_price' => 'required|numeric|min:1000|max:1000000',
        ]);

        $hotel = Hotel::findOrFail($data['hotel_id']);

        // Today's cheapest price for this hotel across active sources
        $latestDate = DB::table('price_snapshots')->max('snapshot_date');

        $best = DB::table('price_snapshots as ps')
            ->join('sources as s', 's.id', '=', 'ps.source_id')
            ->where('s.is_active', true)
            ->where('ps.hotel_id', $hotel->id)
            ->where('ps.snapshot_date', $latestDate)
            ->min('ps.price');

        // A target at or above today's price would fire immediately
        if ($best !== null && (float) $data['target_price'] >= (float) $best) {
            $message = 'Your target must be lower than today\'s best price (¥'.number_format($best).').';

            return response()->json([
                'message' => $message,
                'errors'  => ['target_price' => [$message]],
            ], 422);
        }

        // One alert per person per hotel: setting it again updates the target
        $alert = PriceAlert::updateOrCreate(
            [
                'hotel_id' => $hotel->id,
                'email'    => strtolower($data['email']),
            ],
            [
                'target_price'     => $data['target_price'],
                'is_active'        => true,
                'last_notified_at' => null,
            ]
        );

        return response()->json([
            'message' => 'Alert set. We\'ll email you when '.$hotel->name.' drops to ¥'
                .number_format($data['target_price']).' or lower.',
            'alert' => [
                'id'           => $alert->id,
                'target_price' => (float) $alert->target_price,
            ],
        ], $alert->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * One-click unsubscribe from the link in the email (signed URL).
     */
    public function unsubscribe(PriceAlert $alert)
    {
        $alert->update(['is_active' => false]);

        return view('unsubscribed', ['hotel' => $alert->hotel]);
    }
}