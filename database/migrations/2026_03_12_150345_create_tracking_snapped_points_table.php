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
        Schema::create('tracking_snapped_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tracking_id')->constrained()->cascadeOnDelete();
            $table->integer('road_id')->nullable();
            $table->string('road_type')->nullable();
            $table->decimal('snapped_lat', 15, 10);
            $table->decimal('snapped_lon', 15, 10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracking_snapped_points');
    }
};
