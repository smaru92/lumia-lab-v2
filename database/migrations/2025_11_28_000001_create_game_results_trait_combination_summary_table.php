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
        Schema::create('game_results_trait_combination_summary', function (Blueprint $table) {
            $table->id();
            $table->integer('character_id');
            $table->string('character_name');
            $table->string('weapon_type');
            $table->string('trait_ids')->comment('정렬된 특성 ID 조합 (예: 101,205,308)');
            $table->string('min_tier');
            $table->integer('min_score');
            $table->integer('game_count');
            $table->integer('positive_game_count');
            $table->integer('negative_game_count');
            $table->decimal('game_count_percent', 10, 3)->comment('해당 캐릭터 내 픽률');
            $table->decimal('positive_game_count_percent', 10, 3);
            $table->decimal('negative_game_count_percent', 10, 3);
            $table->integer('top1_count');
            $table->integer('top2_count');
            $table->integer('top4_count');
            $table->decimal('top1_count_percent', 10, 3);
            $table->decimal('top2_count_percent', 10, 3);
            $table->decimal('top4_count_percent', 10, 3);
            $table->decimal('endgame_win_percent', 10, 3)->nullable();
            $table->decimal('avg_mmr_gain', 10, 3);
            $table->decimal('positive_avg_mmr_gain', 10, 3);
            $table->decimal('negative_avg_mmr_gain', 10, 3);
            $table->decimal('avg_team_kill_score', 10, 3)->nullable();
            $table->smallInteger('version_season');
            $table->smallInteger('version_major');
            $table->smallInteger('version_minor');
            $table->unique(
                ['character_id', 'weapon_type', 'trait_ids', 'min_tier', 'version_season', 'version_major', 'version_minor'],
                'trait_combination_summary_unique'
            );
            $table->index(['version_season', 'version_major', 'version_minor', 'min_tier'], 'trait_combination_version_tier_idx');
            $table->index(['character_id', 'weapon_type'], 'trait_combination_char_weapon_idx');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_results_trait_combination_summary');
    }
};