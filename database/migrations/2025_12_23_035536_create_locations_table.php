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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->index();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('tracking_sessions')->cascadeOnDelete();
            $table->foreignId('start_tracking_id')->constrained('trackings')->cascadeOnDelete();
            $table->foreignId('end_tracking_id')->nullable()->constrained('trackings')->nullOnDelete();
            $table->double('speed')->nullable();
            $table->double('distance')->nullable();
            $table->integer('duration');
            $table->timestamp('timestamp')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'timestamp']);
            $table->index(['session_id', 'timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
