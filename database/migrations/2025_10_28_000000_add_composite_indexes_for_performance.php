<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 기존 인덱스 중복 확인 후 복합 인덱스 추가

        // game_results_summary - 메인 페이지 쿼리 최적화
        // character_id 사용하여 인덱스 길이 문제 해결
        if (!$this->indexExists('game_results_summary', 'idx_summary_composite')) {
            DB::statement('CREATE INDEX idx_summary_composite ON game_results_summary(version_season, version_major, version_minor, min_tier, character_id, weapon_type(50))');
        }

        // game_results_rank_summary - 랭크 통계 쿼리 최적화
        if (!$this->indexExists('game_results_rank_summary', 'idx_rank_composite')) {
            DB::statement('CREATE INDEX idx_rank_composite ON game_results_rank_summary(version_season, version_major, version_minor, min_tier, character_id, weapon_type(50), game_rank)');
        }

        // game_results_tactical_skill_summary - 전술 스킬 쿼리 최적화
        if (!$this->indexExists('game_results_tactical_skill_summary', 'idx_tactical_composite')) {
            DB::statement('CREATE INDEX idx_tactical_composite ON game_results_tactical_skill_summary(version_season, version_major, version_minor, min_tier, character_id, weapon_type(50))');
        }

        // game_results_trait_summary - 특성 쿼리 최적화
        if (!$this->indexExists('game_results_trait_summary', 'idx_trait_composite')) {
            DB::statement('CREATE INDEX idx_trait_composite ON game_results_trait_summary(version_season, version_major, version_minor, min_tier, character_id, weapon_type(50))');
        }

        // game_results_equipment_summary - 장비 쿼리 최적화
        if (!$this->indexExists('game_results_equipment_summary', 'idx_equipment_composite')) {
            DB::statement('CREATE INDEX idx_equipment_composite ON game_results_equipment_summary(version_season, version_major, version_minor, min_tier, character_id, weapon_type(50))');
        }

        // game_results_equipment_main_summary - 메인 장비 쿼리 최적화
        if (Schema::hasTable('game_results_equipment_main_summary')) {
            if (!$this->indexExists('game_results_equipment_main_summary', 'idx_equipment_main_composite')) {
                DB::statement('CREATE INDEX idx_equipment_main_composite ON game_results_equipment_main_summary(version_season, version_major, version_minor, min_tier, weapon_type(50))');
            }
        }

        // game_results_first_equipment_main_summary - 첫 장비 쿼리 최적화
        if (Schema::hasTable('game_results_first_equipment_main_summary')) {
            if (!$this->indexExists('game_results_first_equipment_main_summary', 'idx_first_equipment_composite')) {
                DB::statement('CREATE INDEX idx_first_equipment_composite ON game_results_first_equipment_main_summary(version_season, version_major, version_minor, min_tier, weapon_type(50))');
            }
        }
    }

    public function down()
    {
        $this->dropIndexIfExists('game_results_summary', 'idx_summary_composite');
        $this->dropIndexIfExists('game_results_rank_summary', 'idx_rank_composite');
        $this->dropIndexIfExists('game_results_tactical_skill_summary', 'idx_tactical_composite');
        $this->dropIndexIfExists('game_results_trait_summary', 'idx_trait_composite');
        $this->dropIndexIfExists('game_results_equipment_summary', 'idx_equipment_composite');

        if (Schema::hasTable('game_results_equipment_main_summary')) {
            $this->dropIndexIfExists('game_results_equipment_main_summary', 'idx_equipment_main_composite');
        }

        if (Schema::hasTable('game_results_first_equipment_main_summary')) {
            $this->dropIndexIfExists('game_results_first_equipment_main_summary', 'idx_first_equipment_composite');
        }
    }

    private function indexExists($tableName, $indexName)
    {
        $database = env('DB_DATABASE');

        $result = DB::selectOne("
            SELECT COUNT(*) as count
            FROM information_schema.statistics
            WHERE table_schema = ?
            AND table_name = ?
            AND index_name = ?
        ", [$database, $tableName, $indexName]);

        return $result && $result->count > 0;
    }

    private function dropIndexIfExists($tableName, $indexName)
    {
        if ($this->indexExists($tableName, $indexName)) {
            DB::statement("ALTER TABLE `{$tableName}` DROP INDEX `{$indexName}`");
        }
    }
};