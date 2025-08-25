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
        Schema::create('game_results_rank_summary', function (Blueprint $table) {
            $table->id();
            $table->integer('character_id')->nullable();
            $table->string('character_name')->nullable();
            $table->string('weapon_type')->nullable();
            $table->integer('game_rank')->nullable();
            $table->integer('game_rank_count')->nullable();
            $table->decimal('avg_mmr_gain', 10, 3)->nullable();
            $table->integer('positive_count')->nullable();
            $table->integer('negative_count')->nullable();
            $table->decimal('positive_avg_mmr_gain', 10, 3)->nullable();
            $table->decimal('negative_avg_mmr_gain', 10, 3)->nullable();
            $table->string('min_tier')->nullable();
            $table->integer('min_score')->nullable();
            $table->integer('version_major')->nullable();
            $table->integer('version_minor')->nullable();
            $table->unique(['character_id', 'weapon_type', 'min_tier', 'version_major', 'version_minor', 'game_rank'], 'game_result_rank_summary_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_results_rank_summary');
    }
};
