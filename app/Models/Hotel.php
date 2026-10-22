<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hotel extends Model
{
    protected $fillable = [
        'name',
        'city',
        'area',
        'star_rating',
        'review_score',
        'image_url',
        'description',
    ];

    public function priceSnapshots(): HasMany
    {
        return $this->hasMany(PriceSnapshot::class);
    }

    public function priceAlerts(): HasMany
    {
        return $this->hasMany(PriceAlert::class);
    }
}