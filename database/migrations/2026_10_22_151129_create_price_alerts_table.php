<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->decimal('target_price', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();

            // The alert job looks up active alerts per hotel
            $table->index(['hotel_id', 'is_active'], 'idx_alert_hotel_active');

            // A person can't subscribe to the same hotel twice
            $table->unique(['hotel_id', 'email'], 'uniq_alert_hotel_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_alerts');
    }
};