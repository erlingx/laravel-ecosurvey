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
        // Enable PostGIS extension
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');

        Schema::create('survey_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('area_km2', 10, 2)->nullable();
            $table->timestamps();
        });

        // Add PostGIS geography column for polygon (SRID 4326 = WGS84)
        DB::statement('ALTER TABLE survey_zones ADD COLUMN area geography(POLYGON, 4326)');

        // Add spatial index for performance
        DB::statement('CREATE INDEX survey_zones_area_idx ON survey_zones USING GIST (area)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_zones');
    }
};
