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
        Schema::create('bookings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulidMorphs('bookingable');
            $table->foreignUlid('schedule_id')->nullable();
            $table->foreignUlid('event_id');
            $table->dateTime('event_date_time');
            $table->string('bookingable_description', 2048);
            $table->string('reference_code', 10)->unique();
            $table->decimal('total_price', 10, 2)->nullable();
//            $table->string('contact_name', 64);
            $table->string('contact_email', 64);
            $table->string('contact_phone_number', 64);
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
        Schema::dropIfExists('bookings');
    }
};
