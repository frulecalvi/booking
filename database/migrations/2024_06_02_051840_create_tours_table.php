<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tours', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tour_category_id')->nullable();
            $table->string('name', 128);
            $table->string('description', 2048);
            $table->string('short_description', 256);
            $table->time('duration');
            $table->string('meeting_point', 128);
            $table->date('end_date');
            $table->integer('capacity');
            $table->integer('minimum_payment_quantity');
            $table->boolean('bookings_impact_availability');
            $table->boolean('book_without_payment');
            $table->string('image')->nullable();
            $table->string('state');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tours');
    }
};
