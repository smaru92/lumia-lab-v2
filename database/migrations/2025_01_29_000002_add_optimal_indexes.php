<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 최적화된 복합 인덱스만 추가 (DROP 없이)

        Schema::table('game_results_tactical_skill_summary', function (Blueprint $table) {
            $table->index([
                'character_id',
                'weapon_type',
                'version_season',
                'version_major',
                'version_minor',
                'min_tier'
            ], 'idx_tactical_optimal');
        });

        Schema::table('game_results_trait_summary', function (Blueprint $table) {
            $table->index([
                'character_id',
                'weapon_type',
                'version_season',
                'version_major',
                'version_minor',
                'min_tier'
            ], 'idx_trait_optimal');
        });

        Schema::table('game_results_equipment_summary', function (Blueprint $table) {
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