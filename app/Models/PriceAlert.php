<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceAlert extends Model
{
    protected $fillable = [
        'hotel_id',
        'email',
        'target_price',
        'is_active',
        'last_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'target_price' => 'decimal:2',
            'is_active' => 'boolean',
            'last_notified_at' => 'datetime',
        ];
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }
}