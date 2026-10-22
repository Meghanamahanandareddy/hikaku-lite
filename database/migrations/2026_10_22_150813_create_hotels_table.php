<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('city', 100);
            $table->string('area', 100)->nullable();
            $table->unsignedTinyInteger('star_rating')->default(3);
            $table->decimal('review_score', 3, 1)->nullable();
            $table->string('image_url')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('city');
            $table->index('star_rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};