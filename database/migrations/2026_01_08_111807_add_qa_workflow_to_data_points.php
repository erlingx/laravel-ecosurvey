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
            $table->json('qa_flags')->nullable()->after('review_notes');
            $table->string('device_model')->nullable()->after('qa_flags');
            $table->string('sensor_type')->nullable()->after('device_model');
            $table->timestamp('calibration_at')->nullable()->after('sensor_type');
            $table->string('protocol_version')->default('1.0')->after('calibration_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_points', function (Blueprint $table) {
            $table->dropColumn(['qa_flags', 'device_model', 'sensor_type', 'calibration_at', 'protocol_version']);
        });
    }
};
