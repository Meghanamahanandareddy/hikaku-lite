<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceSnapshot extends Model
{
    protected $fillable = [
        'hotel_id',
        'source_id',
        'price',
        'currency',
        'snapshot_date',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'snapshot_date' => 'date',
        ];
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }
}