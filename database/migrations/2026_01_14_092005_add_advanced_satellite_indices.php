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
        Schema::table('satellite_analyses', function (Blueprint $table) {
            $table->decimal('evi_value', 5, 3)->nullable()->after('ndmi_value')->comment('Enhanced Vegetation Index (-1 to 1)');
            $table->decimal('savi_value', 5, 3)->nullable()->after('evi_value')->comment('Soil-Adjusted Vegetation Index (-1 to 1)');
            $table->decimal('ndre_value', 5, 3)->nullable()->after('savi_value')->comment('Normalized Difference Red Edge (-1 to 1)');
            $table->decimal('msi_value', 5, 3)->nullable()->after('ndre_value')->comment('Moisture Stress Index (0 to 3+)');
            $table->decimal('gndvi_value', 5, 3)->nullable()->after('msi_value')->comment('Green NDVI (-1 to 1)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('satellite_analyses', function (Blueprint $table) {
            $table->dropColumn(['evi_value', 'savi_value', 'ndre_value', 'msi_value', 'gndvi_value']);
        });
    }
};
