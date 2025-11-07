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
        // game_results_equipment_main_summary 테이블 인덱스
        Schema::table('game_results_equipment_main_summary', function (Blueprint $table) {
            // 장비 페이지 쿼리 최적화
            $table->index(
                ['version_season', 'version_major', 'version_minor', 'min_tier'],
                'idx_equip_main_version_tier'
            );

            // 정렬 최적화
            $table->index('meta_score', 'idx_equip_main_meta_score');
        });

        // game_results_first_equipment_main_summary 테이블 인덱스
        Schema::table('game_results_first_equipment_main_summary', function (Blueprint $table) {
            // 초기 장비 페이지 쿼리 최적화
            $table->index(
                ['version_season', 'version_major', 'version_minor', 'min_tier'],
                'idx_first_equip_version_tier'
            );

            // 정렬 최적화
            $table->index('meta_score', 'idx_first_equip_meta_score');
        });

        // game_results_equipment_summary 테이블 인덱스
        Schema::table('game_results_equipment_summary', function (Blueprint $table) {
            // 상세 페이지 장비 탭 최적화
            $table->index(
                ['version_season', 'version_major', 'version_minor', 'min_tier', 'character_id', 'weapon_type'],
                'idx_equip_detail_lookup'
            );
        });

        // game_results_tactical_skill_summary 테이블 인덱스
        Schema::table('game_results_tactical_skill_summary', function (Blueprint $table) {
            // 상세 페이지 전술스킬 탭 최적화
            $table->index(
                ['version_season', 'version_major', 'version_minor', 'min_tier', 'character_id', 'weapon_type'],
                'idx_tactical_detail_lookup'
            );
        });

        // game_results_trait_summary 테이블 인덱스
        Schema::table('game_results_trait_summary', function (Blueprint $table) {
            // 상세 페이지 특성 탭 최적화
            $table->index(
                ['version_season', 'version_major', 'version_minor', 'min_tier', 'character_id', 'weapon_type'],
                'idx_trait_detail_lookup'
            );
        });

        // game_results_rank_summary 테이블 인덱스
        Schema::table('game_results_rank_summary', function (Blueprint $table) {
            // 상세 페이지 순위 탭 최적화
            $table->index(
                ['version_season', 'version_major', 'version_minor', 'min_tier', 'character_id', 'weapon_type'],
                'idx_rank_detail_lookup'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_results_equipment_main_summary', function (Blueprint $table) {
            $table->dropIndex('idx_equip_main_version_tier');
            $table->dropIndex('idx_equip_main_meta_score');
        });

        Schema::table('game_results_first_equipment_main_summary', function (Blueprint $table) {
            $table->dropIndex('idx_first_equip_version_tier');
            $table->dropIndex('idx_first_equip_meta_score');
        });

        Schema::table('game_results_equipment_summary', function (Blueprint $table) {
            $table->dropIndex('idx_equip_detail_lookup');
        });

        Schema::table('game_results_tactical_skill_summary', function (Blueprint $table) {
            $table->dropIndex('idx_tactical_detail_lookup');
        });

        Schema::table('game_results_trait_summary', function (Blueprint $table) {
            $table->dropIndex('idx_trait_detail_lookup');
        });

        Schema::table('game_results_rank_summary', function (Blueprint $table) {
            $table->dropIndex('idx_rank_detail_lookup');
        });
    }
};
