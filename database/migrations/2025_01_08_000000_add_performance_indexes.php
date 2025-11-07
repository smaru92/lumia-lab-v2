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
        // game_results_summary 테이블 인덱스 추가
        Schema::table('game_results_summary', function (Blueprint $table) {
            // 메인 페이지 쿼리 최적화를 위한 복합 인덱스
            $table->index(
                ['version_season', 'version_major', 'version_minor', 'min_tier', 'character_id'],
                'idx_summary_version_tier_character'
            );

            // weapon_type 포함 인덱스
            $table->index(
                ['version_season', 'version_major', 'version_minor', 'min_tier', 'character_id', 'weapon_type'],
                'idx_summary_full_lookup'
            );
        });

        // version_histories 테이블 인덱스
        Schema::table('version_histories', function (Blueprint $table) {
            // 버전 정렬을 위한 인덱스
            $table->index(
                ['version_season', 'version_major', 'version_minor'],
                'idx_version_ordering'
            );
        });

        // patch_notes 테이블 인덱스
        Schema::table('patch_notes', function (Blueprint $table) {
            // 패치노트 조회 최적화
            $table->index(
                ['version_history_id', 'category', 'target_id'],
                'idx_patchnotes_lookup'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_results_summary', function (Blueprint $table) {
            $table->dropIndex('idx_summary_version_tier_character');
            $table->dropIndex('idx_summary_full_lookup');
        });

        Schema::table('version_histories', function (Blueprint $table) {
            $table->dropIndex('idx_version_ordering');
        });

        Schema::table('patch_notes', function (Blueprint $table) {
            $table->dropIndex('idx_patchnotes_lookup');
        });
    }
};
