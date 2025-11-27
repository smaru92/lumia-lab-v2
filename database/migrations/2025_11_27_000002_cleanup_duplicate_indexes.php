<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 중복 인덱스 정리 마이그레이션
 *
 * 각 테이블에 이미 UNIQUE 인덱스가 있어서 대부분의 쿼리를 커버함.
 * 추가 인덱스들 중 중복되는 것들을 정리하여 인덱스 크기를 줄임.
 */
return new class extends Migration
{
    public function up(): void
    {
        // =====================================================
        // 1. game_results_summary
        // =====================================================
        // 유지: game_result_summary_unique (character_id, weapon_type, min_tier, version_major, version_minor)
        // 유지: idx_summary_composite (version_season, version_major, version_minor, min_tier, character_id, weapon_type) - 주요 조회용
        // 삭제: idx_game_summary_version_tier - idx_summary_composite의 서브셋
        // 삭제: idx_game_summary_char_weapon - unique 인덱스로 커버됨
        // 삭제: idx_game_summary_char_id - unique 인덱스로 커버됨
        $this->dropIndexIfExists('game_results_summary', 'idx_game_summary_version_tier');
        $this->dropIndexIfExists('game_results_summary', 'idx_game_summary_char_weapon');
        $this->dropIndexIfExists('game_results_summary', 'idx_game_summary_char_id');

        // =====================================================
        // 2. game_results_rank_summary
        // =====================================================
        // 유지: game_result_rank_summary_unique (character_id, weapon_type, min_tier, version_major, version_minor, game_rank)
        // 유지: idx_rank_composite (version_season, version_major, version_minor, min_tier, character_id, weapon_type, game_rank) - 주요 조회용
        // 삭제: idx_rank_summary_version_tier - idx_rank_composite의 서브셋
        // 삭제: idx_rank_summary_char_rank - unique 인덱스로 커버됨
        // 삭제: idx_rank_summary_char_id - unique 인덱스로 커버됨
        // 삭제: idx_rank_detail_lookup - idx_rank_composite의 서브셋
        $this->dropIndexIfExists('game_results_rank_summary', 'idx_rank_summary_version_tier');
        $this->dropIndexIfExists('game_results_rank_summary', 'idx_rank_summary_char_rank');
        $this->dropIndexIfExists('game_results_rank_summary', 'idx_rank_summary_char_id');
        $this->dropIndexIfExists('game_results_rank_summary', 'idx_rank_detail_lookup');

        // =====================================================
        // 3. game_results_equipment_summary
        // =====================================================
        // 유지: game_result_rank_summary_unique (character_id, weapon_type, equipment_id, min_tier, version_major, version_minor, game_rank)
        // 유지: idx_equipment_composite (version_season, version_major, version_minor, min_tier, character_id, weapon_type) - 주요 조회용
        // 삭제: idx_equip_detail_lookup - idx_equipment_composite와 거의 동일
        // 삭제: idx_equipment_summary_version - idx_equipment_composite의 서브셋
        // 삭제: idx_char_equipment - unique 인덱스로 커버됨
        // 삭제: idx_equipment_weapon - 단일 컬럼 인덱스, 복합 인덱스로 커버됨
        $this->dropIndexIfExists('game_results_equipment_summary', 'idx_equip_detail_lookup');
        $this->dropIndexIfExists('game_results_equipment_summary', 'idx_equipment_summary_version');
        $this->dropIndexIfExists('game_results_equipment_summary', 'idx_char_equipment');
        $this->dropIndexIfExists('game_results_equipment_summary', 'idx_equipment_weapon');

        // =====================================================
        // 4. game_results_trait_summary
        // =====================================================
        // 유지: game_results_trait_summary_unique (character_id, weapon_type, trait_id, min_tier, version_major, version_minor, game_rank)
        // 유지: idx_trait_composite (version_season, version_major, version_minor, min_tier, character_id, weapon_type) - 주요 조회용
        // 삭제: idx_trait_summary_version_tier - idx_trait_composite의 서브셋
        // 삭제: idx_trait_summary_char_weapon - unique 인덱스로 커버됨
        // 삭제: idx_trait_summary_trait - unique 인덱스로 커버됨
        // 삭제: idx_trait_detail_lookup - idx_trait_composite와 거의 동일
        $this->dropIndexIfExists('game_results_trait_summary', 'idx_trait_summary_version_tier');
        $this->dropIndexIfExists('game_results_trait_summary', 'idx_trait_summary_char_weapon');
        $this->dropIndexIfExists('game_results_trait_summary', 'idx_trait_summary_trait');
        $this->dropIndexIfExists('game_results_trait_summary', 'idx_trait_detail_lookup');

        // =====================================================
        // 5. game_results_tactical_skill_summary
        // =====================================================
        // 유지: game_results_tactical_skill_summary_unique (character_id, weapon_type, tactical_skill_id, tactical_skill_level, min_tier, version_major, version_minor, game_rank)
        // 유지: idx_tactical_composite (version_season, version_major, version_minor, min_tier, character_id, weapon_type) - 주요 조회용
        // 삭제: idx_tactical_summary_version - idx_tactical_composite의 서브셋
        // 삭제: idx_tactical_summary_char - unique 인덱스로 커버됨
        // 삭제: idx_tactical_skill - unique 인덱스로 커버됨
        // 삭제: idx_tactical_detail_lookup - idx_tactical_composite와 거의 동일
        $this->dropIndexIfExists('game_results_tactical_skill_summary', 'idx_tactical_summary_version');
        $this->dropIndexIfExists('game_results_tactical_skill_summary', 'idx_tactical_summary_char');
        $this->dropIndexIfExists('game_results_tactical_skill_summary', 'idx_tactical_skill');
        $this->dropIndexIfExists('game_results_tactical_skill_summary', 'idx_tactical_detail_lookup');

        // =====================================================
        // 6. game_results_equipment_main_summary
        // =====================================================
        // 유지: game_results_equipment_main_summary_unique (equipment_id, min_tier, version_major, version_minor)
        // 유지: idx_equipment_main_composite (version_season, version_major, version_minor, min_tier, equipment_id) - 주요 조회용
        // 삭제: idx_equip_main_version_tier - idx_equipment_main_composite의 서브셋
        // 유지: idx_equip_main_meta_score - 정렬용으로 유지
        if (Schema::hasTable('game_results_equipment_main_summary')) {
            $this->dropIndexIfExists('game_results_equipment_main_summary', 'idx_equip_main_version_tier');
        }

        // =====================================================
        // 7. game_results_first_equipment_main_summary
        // =====================================================
        // 유지: game_results_equipment_main_summary_unique (equipment_id, min_tier, version_major, version_minor)
        // 유지: idx_first_equipment_composite (version_season, version_major, version_minor, min_tier, equipment_id) - 주요 조회용
        // 삭제: idx_first_equip_version_tier - idx_first_equipment_composite의 서브셋
        // 유지: idx_first_equip_meta_score - 정렬용으로 유지
        if (Schema::hasTable('game_results_first_equipment_main_summary')) {
            $this->dropIndexIfExists('game_results_first_equipment_main_summary', 'idx_first_equip_version_tier');
        }

        // =====================================================
        // 8. 버전별 game_results 테이블들 (game_results_v*)
        // =====================================================
        $this->cleanupVersionedGameResultsTables();

        // =====================================================
        // 9. 버전별 equipment_orders 테이블들
        // =====================================================
        $this->cleanupVersionedEquipmentOrdersTables();
    }

    /**
     * 버전별 game_results 테이블의 중복 인덱스 정리
     */
    private function cleanupVersionedGameResultsTables(): void
    {
        $tables = DB::select("SHOW TABLES LIKE 'game_results_v%'");

        foreach ($tables as $tableObj) {
            $tableName = array_values((array)$tableObj)[0];

            // 관련 테이블 제외 (skill_orders, equipment_orders, trait_orders 등)
            if (str_contains($tableName, '_orders') || str_contains($tableName, '_summary')) {
                continue;
            }

            // 삭제: idx_game_results_version - idx_gr_ver_char_rank_id의 서브셋
            $this->dropIndexIfExists($tableName, 'idx_game_results_version');

            // 삭제: idx_gr_char_rank_mmr - idx_game_results_character_weapon_rank의 서브셋
            $this->dropIndexIfExists($tableName, 'idx_gr_char_rank_mmr');

            // 삭제: idx_gr_version_mmr - idx_gr_version_mmr_gain_rank의 서브셋
            $this->dropIndexIfExists($tableName, 'idx_gr_version_mmr');
        }
    }

    /**
     * 버전별 equipment_orders 테이블의 중복 인덱스 정리
     */
    private function cleanupVersionedEquipmentOrdersTables(): void
    {
        // game_result_equipment_orders 테이블들
        $equipTables = DB::select("SHOW TABLES LIKE 'game_result_equipment_orders_v%'");
        foreach ($equipTables as $tableObj) {
            $tableName = array_values((array)$tableObj)[0];
            // 삭제: idx_gre_game_result_id - idx_gre_result_equip의 서브셋
            $this->dropIndexIfExists($tableName, 'idx_gre_game_result_id');
        }

        // game_result_first_equipment_orders 테이블들
        $firstEquipTables = DB::select("SHOW TABLES LIKE 'game_result_first_equipment_orders_v%'");
        foreach ($firstEquipTables as $tableObj) {
            $tableName = array_values((array)$tableObj)[0];
            // 삭제: idx_gre_game_result_id - idx_gre_result_equip의 서브셋
            $this->dropIndexIfExists($tableName, 'idx_gre_game_result_id');
        }
    }

    public function down(): void
    {
        // 복원: game_results_summary
        $this->createIndexIfNotExists('game_results_summary', 'idx_game_summary_version_tier',
            ['version_season', 'version_major', 'version_minor', 'min_tier']);
        $this->createIndexIfNotExists('game_results_summary', 'idx_game_summary_char_weapon',
            ['character_name', 'weapon_type']);
        $this->createIndexIfNotExists('game_results_summary', 'idx_game_summary_char_id',
            ['character_id']);

        // 복원: game_results_rank_summary
        $this->createIndexIfNotExists('game_results_rank_summary', 'idx_rank_summary_version_tier',
            ['version_season', 'version_major', 'version_minor', 'min_tier']);
        $this->createIndexIfNotExists('game_results_rank_summary', 'idx_rank_summary_char_rank',
            ['character_name', 'weapon_type', 'game_rank']);
        $this->createIndexIfNotExists('game_results_rank_summary', 'idx_rank_summary_char_id',
            ['character_id']);
        DB::statement('CREATE INDEX idx_rank_detail_lookup ON game_results_rank_summary(version_season, version_major, version_minor, min_tier, character_id, weapon_type(50))');

        // 복원: game_results_equipment_summary
        DB::statement('CREATE INDEX idx_equip_detail_lookup ON game_results_equipment_summary(version_season, version_major, version_minor, min_tier, character_id, weapon_type(50))');
        $this->createIndexIfNotExists('game_results_equipment_summary', 'idx_equipment_summary_version',
            ['version_season', 'version_major', 'version_minor', 'min_tier']);
        $this->createIndexIfNotExists('game_results_equipment_summary', 'idx_char_equipment',
            ['character_id', 'equipment_id']);
        $this->createIndexIfNotExists('game_results_equipment_summary', 'idx_equipment_weapon',
            ['weapon_type']);

        // 복원: game_results_trait_summary
        $this->createIndexIfNotExists('game_results_trait_summary', 'idx_trait_summary_version_tier',
            ['version_season', 'version_major', 'version_minor', 'min_tier']);
        $this->createIndexIfNotExists('game_results_trait_summary', 'idx_trait_summary_char_weapon',
            ['character_id', 'weapon_type']);
        $this->createIndexIfNotExists('game_results_trait_summary', 'idx_trait_summary_trait',
            ['trait_id', 'is_main']);
        DB::statement('CREATE INDEX idx_trait_detail_lookup ON game_results_trait_summary(version_season, version_major, version_minor, min_tier, character_id, weapon_type(50))');

        // 복원: game_results_tactical_skill_summary
        $this->createIndexIfNotExists('game_results_tactical_skill_summary', 'idx_tactical_summary_version',
            ['version_season', 'version_major', 'version_minor', 'min_tier']);
        $this->createIndexIfNotExists('game_results_tactical_skill_summary', 'idx_tactical_summary_char',
            ['character_id', 'weapon_type']);
        $this->createIndexIfNotExists('game_results_tactical_skill_summary', 'idx_tactical_skill',
            ['tactical_skill_id', 'tactical_skill_level']);
        DB::statement('CREATE INDEX idx_tactical_detail_lookup ON game_results_tactical_skill_summary(version_season, version_major, version_minor, min_tier, character_id, weapon_type(50))');

        // 복원: game_results_equipment_main_summary
        if (Schema::hasTable('game_results_equipment_main_summary')) {
            $this->createIndexIfNotExists('game_results_equipment_main_summary', 'idx_equip_main_version_tier',
                ['version_season', 'version_major', 'version_minor', 'min_tier']);
        }

        // 복원: game_results_first_equipment_main_summary
        if (Schema::hasTable('game_results_first_equipment_main_summary')) {
            $this->createIndexIfNotExists('game_results_first_equipment_main_summary', 'idx_first_equip_version_tier',
                ['version_season', 'version_major', 'version_minor', 'min_tier']);
        }
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        $database = config('database.connections.mysql.database');

        $result = DB::selectOne("
            SELECT COUNT(*) as count
            FROM information_schema.statistics
            WHERE table_schema = ?
            AND table_name = ?
            AND index_name = ?
        ", [$database, $tableName, $indexName]);

        return $result && $result->count > 0;
    }

    private function dropIndexIfExists(string $tableName, string $indexName): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        if ($this->indexExists($tableName, $indexName)) {
            DB::statement("ALTER TABLE `{$tableName}` DROP INDEX `{$indexName}`");
        }
    }

    private function createIndexIfNotExists(string $tableName, string $indexName, array $columns): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        if (!$this->indexExists($tableName, $indexName)) {
            $columnList = implode(', ', array_map(fn($col) => "`{$col}`", $columns));
            DB::statement("CREATE INDEX `{$indexName}` ON `{$tableName}` ({$columnList})");
        }
    }
};
