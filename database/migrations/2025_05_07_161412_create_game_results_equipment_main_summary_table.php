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
        Schema::create('game_results_equipment_main_summary', function (Blueprint $table) {
            $table->id();
            $table->integer('equipment_id');
            $table->string('equipment_name');
            $table->string('meta_tier')->nullable();
            $table->decimal('meta_score', 10, 3)->nullable();
            $table->string('min_tier');
            $table->integer('min_score');
            $table->integer('game_count');
            $table->integer('positive_game_count');
            $table->integer('negative_game_count');
            $table->decimal('game_count_percent', 10, 3);
            $table->decimal('positive_game_count_percent', 10, 3);
            $table->decimal('negative_game_count_percent', 10, 3);
            $table->integer('top1_count');
            $table->integer('top2_count');
            $table->integer('top4_count');
            $table->decimal('top1_count_percent', 10, 3);
            $table->decimal('top2_count_percent', 10, 3);
            $table->decimal('top4_count_percent', 10, 3);
            $table->decimal('avg_mmr_gain', 10, 3);
            $table->decimal('positive_avg_mmr_gain', 10, 3);
            $table->decimal('negative_avg_mmr_gain', 10, 3);
            $table->smallInteger('version_major');
            $table->smallInteger('version_minor');
            $table->unique(['equipment_id', 'min_tier', 'version_major', 'version_minor'], 'game_results_equipment_main_summary_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_results_equipment_main_summary');
    }
};
