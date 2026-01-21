<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_meters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('resource', 50);
            $table->integer('count')->default(0);
            $table->date('billing_cycle_start');
            $table->date('billing_cycle_end');
            $table->timestamps();
            $table->unique(['user_id', 'resource', 'billing_cycle_start']);
            $table->index(['user_id', 'billing_cycle_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_meters');
    }
};
