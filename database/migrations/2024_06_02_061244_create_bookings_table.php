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
            $table->foreignUlid('event_id');
            $table->date('event_date');
            $table->time('event_time');
            $table->foreignUlid('schedule_id');
            $table->ulidMorphs('scheduleable');
            $table->string('scheduleable_description', 256);
            $table->string('reference_code', 10)->unique();
            $table->string('contact_name', 64);
            $table->string('contact_email', 64);
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
