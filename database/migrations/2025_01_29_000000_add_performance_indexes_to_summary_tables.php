<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // game_results_summary 테이블 인덱스 - character_name 존재
        Schema::table('game_results_summary', function (Blueprint $table) {
            $table->index(['version_season', 'version_major', 'version_minor', 'min_tier'], 'idx_game_summary_version_tier');
            $table->index(['character_name', 'weapon_type'], 'idx_game_summary_char_weapon');
            $table->index(['character_id'], 'idx_game_summary_char_id');
            $table->index(['meta_score'], 'idx_meta_score');
        });

        // game_results_rank_summary 테이블 인덱스 - character_name 존재
        Schema::table('game_results_rank_summary', function (Blueprint $table) {
            $table->index(['version_season', 'version_major', 'version_minor', 'min_tier'], 'idx_rank_summary_version_tier');
            $table->index(['character_name', 'weapon_type', 'game_rank'], 'idx_rank_summary_char_rank');
            $table->index(['character_id'], 'idx_rank_summary_char_id');
        });

        // game_results_equipment_summary 테이블 인덱스 - character_id만 존재
        Schema::table('game_results_equipment_summary', function (Blueprint $table) {
            $table->index(['version_season', 'version_major', 'version_minor', 'min_tier'], 'idx_equipment_summary_version');
            $table->index(['character_id', 'equipment_id'], 'idx_char_equipment');
            $table->index(['weapon_type'], 'idx_equipment_weapon');
        });

        // game_results_trait_summary 테이블 인덱스 - character_id만 존재
        Schema::table('game_results_trait_summary', function (Blueprint $table) {
            $table->index(['version_season', 'version_major', 'version_minor', 'min_tier'], 'idx_trait_summary_version_tier');
            $table->index(['character_id', 'weapon_type'], 'idx_trait_summary_char_weapon');
            $table->index(['trait_id', 'is_main'], 'idx_trait_summary_trait');
        });

        // game_results_tactical_skill_summary 테이블 인덱스 - character_id만 존재
        Schema::table('game_results_tactical_skill_summary', function (Blueprint $table) {
            $table->index(['version_season', 'version_major', 'version_minor', 'min_tier'], 'idx_tactical_summary_version');
            $table->index(['character_id', 'weapon_type'], 'idx_tactical_summary_char');
            $table->index(['tactical_skill_id', 'tactical_skill_level'], 'idx_tactical_skill');
        });
    }

    public function down()
    {
        Schema::table('game_results_summary', function (Blueprint $table) {
            $table->dropIndex('idx_game_summary_version_tier');
            $table->dropIndex('idx_game_summary_char_weapon');
            $table->dropIndex('idx_game_summary_char_id');
            $table->dropIndex('idx_meta_score');
        });

        Schema::table('game_results_rank_summary', function (Blueprint $table) {
            $table->dropIndex('idx_rank_summary_version_tier');
            $table->dropIndex('idx_rank_summary_char_rank');
            $table->dropIndex('idx_rank_summary_char_id');
        });

        Schema::table('game_results_equipment_summary', function (Blueprint $table) {
            $table->dropIndex('idx_equipment_summary_version');
            $table->dropIndex('idx_char_equipment');
            $table->dropIndex('idx_equipment_weapon');
        });

        Schema::table('game_results_trait_summary', function (Blueprint $table) {
            $table->dropIndex('idx_trait_summary_version_tier');
            $table->dropIndex('idx_trait_summary_char_weapon');
            $table->dropIndex('idx_trait_summary_trait');
        });

        Schema::table('game_results_tactical_skill_summary', function (Blueprint $table) {
            $table->dropIndex('idx_tactical_summary_version');
            $table->dropIndex('idx_tactical_summary_char');
            $table->dropIndex('idx_tactical_skill');
        });
    }
};