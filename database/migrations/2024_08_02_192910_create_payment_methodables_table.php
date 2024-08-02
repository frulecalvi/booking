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
        Schema::create('payment_methodables', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('payment_method_id')->constrained();
            $table->ulidMorphs('payment_methodable', 'payment_methodables_type_id_index');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methodables');
    }
};
