<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_snapshots', function (Blueprint $table) {
            // Lets MySQL jump straight to one day's prices, and answer the
            // "cheapest price per hotel today" query from the index alone.
            $table->index(
                ['snapshot_date', 'hotel_id', 'source_id', 'price'],
                'idx_date_hotel_source_price'
            );
        });
    }

    public function down(): void
    {
        Schema::table('price_snapshots', function (Blueprint $table) {
            $table->dropIndex('idx_date_hotel_source_price');
        });
    }
};