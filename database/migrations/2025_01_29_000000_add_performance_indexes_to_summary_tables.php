<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // game_results_summary 테이블 인덱스
        Schema::table('game_results_summary', function (Blueprint $table) {
            $table->index(['version_season', 'version_major', 'version_minor', 'min_tier', 'character_name', 'weapon_type'], 'idx_game_summary_composite');
            $table->index(['meta_score'], 'idx_meta_score');
        });

        // game_results_rank_summary 테이블 인덱스
        Schema::table('game_results_rank_summary', function (Blueprint $table) {
            $table->index(['version_season', 'version_major', 'version_minor', 'min_tier', 'character_name', 'weapon_type', 'game_rank'], 'idx_rank_summary_composite');
        });

        // game_results_equipment_summary 테이블 인덱스
        Schema::table('game_results_equipment_summary', function (Blueprint $table) {
            $table->index(['version_season', 'version_major', 'version_minor', 'min_tier', 'weapon_type'], 'idx_equipment_summary_composite');
            $table->index(['character_id', 'equipment_id'], 'idx_char_equipment');
        });

        // game_results_trait_summary 테이블 인덱스
        Schema::table('game_results_trait_summary', function (Blueprint $table) {
            $table->index(['version_season', 'version_major', 'version_minor', 'min_tier', 'character_name', 'weapon_type'], 'idx_trait_summary_composite');
        });

        // game_results_tactical_skill_summary 테이블 인덱스
        Schema::table('game_results_tactical_skill_summary', function (Blueprint $table) {
            $table->index(['version_season', 'version_major', 'version_minor', 'min_tier', 'character_name', 'weapon_type'], 'idx_tactical_summary_composite');
        });
    }

    public function down()
    {
        Schema::table('game_results_summary', function (Blueprint $table) {
            $table->dropIndex('idx_game_summary_composite');
            $table->dropIndex('idx_meta_score');
        });

        Schema::table('game_results_rank_summary', function (Blueprint $table) {
            $table->dropIndex('idx_rank_summary_composite');
        });

        Schema::table('game_results_equipment_summary', function (Blueprint $table) {
            $table->dropIndex('idx_equipment_summary_composite');
            $table->dropIndex('idx_char_equipment');
        });

        Schema::table('game_results_trait_summary', function (Blueprint $table) {
            $table->dropIndex('idx_trait_summary_composite');
        });

        Schema::table('game_results_tactical_skill_summary', function (Blueprint $table) {
            $table->dropIndex('idx_tactical_summary_composite');
        });
    }
};