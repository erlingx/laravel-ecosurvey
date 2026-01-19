<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('environmental_metrics', function (Blueprint $table) {
            $table->decimal('expected_min', 10, 2)->nullable()->after('description');
            $table->decimal('expected_max', 10, 2)->nullable()->after('expected_min');
        });
    }

    public function down(): void
    {
        Schema::table('environmental_metrics', function (Blueprint $table) {
            $table->dropColumn(['expected_min', 'expected_max']);
        });
    }
};
