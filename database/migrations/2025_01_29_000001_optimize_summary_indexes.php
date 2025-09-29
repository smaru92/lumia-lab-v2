<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 기존 비효율적인 인덱스 제거 후 최적화된 복합 인덱스 추가

        // game_results_tactical_skill_summary - 가장 선택적인 컬럼을 먼저
        Schema::table('game_results_tactical_skill_summary', function (Blueprint $table) {
            // 기존 인덱스 제거 (이미 있다면)
            try {
                $table->dropIndex('idx_tactical_optimal');
            } catch (\Exception $e) {}

            // 최적화된 복합 인덱스 (WHERE 조건에 모두 포함)
            $table->index([
                'character_id',
                'weapon_type',
                'version_season',
                'version_major',
                'version_minor',
                'min_tier'
            ], 'idx_tactical_optimal');
        });

        // game_results_trait_summary
        Schema::table('game_results_trait_summary', function (Blueprint $table) {
            try {
                $table->dropIndex('idx_trait_optimal');
            } catch (\Exception $e) {}

            $table->index([
                'character_id',
                'weapon_type',
                'version_season',
                'version_major',
                'version_minor',
                'min_tier'
            ], 'idx_trait_optimal');
        });

        // game_results_equipment_summary
        Schema::table('game_results_equipment_summary', function (Blueprint $table) {
            try {
                $table->dropIndex('idx_equipment_optimal');
            } catch (\Exception $e) {}

            $table->index([
                'character_id',
                'weapon_type',
                'version_season',
                'version_major',
                'version_minor',
                'min_tier'
            ], 'idx_equipment_optimal');
        });
    }

    public function down()
    {
        Schema::table('game_results_tactical_skill_summary', function (Blueprint $table) {
            $table->dropIndex('idx_tactical_optimal');
        });

        Schema::table('game_results_trait_summary', function (Blueprint $table) {
            $table->dropIndex('idx_trait_optimal');
        });

        Schema::table('game_results_equipment_summary', function (Blueprint $table) {
            $table->dropIndex('idx_equipment_optimal');
        });
    }
};
