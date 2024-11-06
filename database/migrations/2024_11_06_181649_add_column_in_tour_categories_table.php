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
        Schema::table('tour_categories', function (Blueprint $table) {
            $table->after('id', function (Blueprint $table) {
                $table->string('slug', 64)->unique();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tour_categories', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};