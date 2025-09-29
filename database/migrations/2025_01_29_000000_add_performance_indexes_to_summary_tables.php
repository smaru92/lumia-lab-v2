<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // game_results_summary 테이블 인덱스 - 분리하여 적용
        Schema::table('game_results_summary', function (Blueprint $table) {
            $table->index(['version_season', 'version_major', 'version_minor', 'min_tier'], 'idx_game_summary_version_tier');
            $table->index(['character_name', 'weapon_type'], 'idx_game_summary_char_weapon');
            $table->index(['meta_score'], 'idx_meta_score');
        });

        // game_results_rank_summary 테이블 인덱스 - 분리하여 적용
        Schema::table('game_results_rank_summary', function (Blueprint $table) {
            $table->index(['version_season', 'version_major', 'version_minor', 'min_tier'], 'idx_rank_summary_version_tier');
            $table->index(['character_name', 'weapon_type', 'game_rank'], 'idx_rank_summary_char_rank');
        });

        // game_results_equipment_summary 테이블 인덱스
        Schema::table('game_results_equipment_summary', function (Blueprint $table) {
            $table->index(['version_season', 'version_major', 'version_minor', 'min_tier'], 'idx_equipment_summary_version');
            $table->index(['character_id', 'equipment_id'], 'idx_char_equipment');
            $table->index(['weapon_type'], 'idx_equipment_weapon');
        });

        // game_results_trait_summary 테이블 인덱스 - 분리하여 적용
        Schema::table('game_results_trait_summary', function (Blueprint $table) {
            $table->index(['version_season', 'version_major', 'version_minor', 'min_tier'], 'idx_trait_summary_version_tier');
            $table->index(['character_name', 'weapon_type'], 'idx_trait_summary_char_weapon');
        });

        // game_results_tactical_skill_summary 테이블 인덱스 - 분리하여 적용
        Schema::table('game_results_tactical_skill_summary', function (Blueprint $table) {
            $table->index(['version_season', 'version_major', 'version_minor', 'min_tier'], 'idx_tactical_summary_version');
            $table->index(['character_name', 'weapon_type'], 'idx_tactical_summary_char');
        });
    }

    public function down()
    {
        Schema::table('game_results_summary', function (Blueprint $table) {
            $table->dropIndex('idx_game_summary_version_tier');
            $table->dropIndex('idx_game_summary_char_weapon');
            $table->dropIndex('idx_meta_score');
        });

        Schema::table('game_results_rank_summary', function (Blueprint $table) {
            $table->dropIndex('idx_rank_summary_version_tier');
            $table->dropIndex('idx_rank_summary_char_rank');
        });

        Schema::table('game_results_equipment_summary', function (Blueprint $table) {
            $table->dropIndex('idx_equipment_summary_version');
            $table->dropIndex('idx_char_equipment');
            $table->dropIndex('idx_equipment_weapon');
        });

        Schema::table('game_results_trait_summary', function (Blueprint $table) {
            $table->dropIndex('idx_trait_summary_version_tier');
            $table->dropIndex('idx_trait_summary_char_weapon');
        });

        Schema::table('game_results_tactical_skill_summary', function (Blueprint $table) {
            $table->dropIndex('idx_tactical_summary_version');
            $table->dropIndex('idx_tactical_summary_char');
        });
    }
};