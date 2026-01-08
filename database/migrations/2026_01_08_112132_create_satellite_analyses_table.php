<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('satellite_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_point_id')->nullable()->constrained('data_points')->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->cascadeOnDelete();

            $table->string('image_url')->nullable();
            $table->decimal('ndvi_value', 5, 4)->nullable();
            $table->text('ndvi_interpretation')->nullable();
            $table->decimal('moisture_index', 5, 4)->nullable();
            $table->decimal('temperature_kelvin', 6, 2)->nullable();

            $table->date('acquisition_date');
            $table->string('satellite_source')->default('Copernicus');
            $table->string('processing_level')->nullable();
            $table->decimal('cloud_coverage_percent', 5, 2)->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('acquisition_date');
            $table->index(['data_point_id', 'acquisition_date']);
            $table->index(['campaign_id', 'acquisition_date']);
        });

        // Add PostGIS geometry column for analysis location (SRID 4326 = WGS84)
        DB::statement('ALTER TABLE satellite_analyses ADD COLUMN location geometry(POINT, 4326)');

        // Add spatial index for location queries
        DB::statement('CREATE INDEX satellite_analyses_location_idx ON satellite_analyses USING GIST (location)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('satellite_analyses');
    }
};
