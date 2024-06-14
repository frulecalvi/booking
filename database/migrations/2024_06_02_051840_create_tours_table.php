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
            $table->string('description', 512);
            $table->time('duration');
            $table->string('meeting_point', 128);
            $table->date('end_date');
            $table->integer('capacity');
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
