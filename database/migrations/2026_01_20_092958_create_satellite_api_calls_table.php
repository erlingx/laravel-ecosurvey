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
        Schema::create('satellite_api_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_point_id')->nullable()->constrained('data_points')->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('call_type'); // 'enrichment', 'overlay', 'analysis'
            $table->string('index_type')->nullable(); // 'ndvi', 'moisture', 'evi', etc.
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->date('acquisition_date')->nullable();

            $table->boolean('cached')->default(false);
            $table->integer('response_time_ms')->nullable();
            $table->decimal('cost_credits', 8, 4)->default(1.0); // For future billing

            $table->timestamps();

            $table->index('call_type');
            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('satellite_api_calls');
    }
};
