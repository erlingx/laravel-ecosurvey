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
        Schema::create('environmental_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_point_id')->constrained('data_points')->cascadeOnDelete();

            $table->string('metric_type');
            $table->decimal('value', 10, 4);
            $table->string('unit');

            $table->decimal('min_value', 10, 4)->nullable();
            $table->decimal('max_value', 10, 4)->nullable();

            $table->enum('source', ['user_input', 'sensor', 'api', 'calculated'])->default('user_input');
            $table->string('source_details')->nullable();

            $table->decimal('confidence_score', 3, 2)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('metric_type');
            $table->index(['metric_type', 'value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('environmental_metrics');
    }
};
