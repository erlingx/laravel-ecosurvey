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
        Schema::create('data_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->foreignId('environmental_metric_id')->constrained('environmental_metrics')->cascadeOnDelete();
            $table->foreignId('survey_zone_id')->nullable()->constrained('survey_zones')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->decimal('value', 10, 2);
            $table->decimal('accuracy', 8, 2)->nullable();

            $table->timestamp('collected_at');
            $table->string('device_info')->nullable();
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();

            $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('collected_at');
            $table->index('status');
        });

        // Add PostGIS geometry column for point location (SRID 4326 = WGS84)
        DB::statement('ALTER TABLE data_points ADD COLUMN location geometry(POINT, 4326)');

        // Add spatial index for location queries
        DB::statement('CREATE INDEX data_points_location_idx ON data_points USING GIST (location)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_points');
    }
};
