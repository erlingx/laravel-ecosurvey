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
        Schema::table('data_points', function (Blueprint $table) {
            $table->decimal('official_value', 10, 2)->nullable()->after('value');
            $table->string('official_station_name')->nullable()->after('official_value');
            $table->decimal('official_station_distance', 10, 2)->nullable()->after('official_station_name');
            $table->decimal('variance_percentage', 10, 2)->nullable()->after('official_station_distance');
            $table->string('satellite_image_url')->nullable()->after('variance_percentage');
            $table->decimal('ndvi_value', 6, 4)->nullable()->after('satellite_image_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_points', function (Blueprint $table) {
            $table->dropColumn([
                'official_value',
                'official_station_name',
                'official_station_distance',
                'variance_percentage',
                'satellite_image_url',
                'ndvi_value',
            ]);
        });
    }
};

