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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['draft', 'active', 'completed', 'archived'])->default('active');
            $table->json('target_metrics')->nullable();
            $table->integer('target_data_points')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Add PostGIS geometry column for survey_zone (polygon)
        DB::statement('ALTER TABLE campaigns ADD COLUMN survey_zone geometry(Polygon, 4326)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
