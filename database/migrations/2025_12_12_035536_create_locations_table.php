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
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('session_id')->constrained('tracking_sessions')->onDelete('cascade');
            $table->decimal('start_latitude', 10, 7);
            $table->decimal('start_longitude', 10, 7);
            $table->decimal('end_latitude', 10, 7);
            $table->decimal('end_longitude', 10, 7);
            $table->double('accuracy');
            $table->double('speed')->nullable();
            $table->double('distance')->nullable();
            $table->integer('interval_seconds')->default(20);
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
