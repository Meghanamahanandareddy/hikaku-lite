<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->char('currency', 3)->default('JPY');
            $table->date('snapshot_date');
            $table->timestamps();

            // One price per hotel, per source, per day
            $table->unique(['hotel_id', 'source_id', 'snapshot_date'], 'uniq_hotel_source_day');

            // Fast price-history lookups for one hotel over a date range
            $table->index(['hotel_id', 'snapshot_date'], 'idx_hotel_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_snapshots');
    }
};