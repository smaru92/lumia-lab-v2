<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 기존의 버전별 game_results 테이블들에 matching_mode 인덱스 추가
     * 이 인덱스가 없어서 Summary 업데이트 쿼리가 풀스캔하여 CPU 점유율이 높았음
     */
    public function up(): void
    {
        // game_results_v* 패턴의 모든 테이블 찾기
        $tables = $this->getVersionedTables('game_results_v%');

        foreach ($tables as $tableName) {
            echo "Adding indexes to {$tableName}...\n";

            // 이미 인덱스가 있는지 확인
            if (!$this->hasIndex($tableName, 'idx_mode_mmr_char')) {
                DB::statement("ALTER TABLE `{$tableName}`
                    ADD INDEX `idx_mode_mmr_char` (`matching_mode`, `mmr_before`, `character_id`)");
            }

            if (!$this->hasIndex($tableName, 'idx_mode_char_weapon')) {
                DB::statement("ALTER TABLE `{$tableName}`
                    ADD INDEX `idx_mode_char_weapon` (`matching_mode`, `character_id`, `weapon_id`)");
            }
        }

        echo "Indexes added to " . count($tables) . " tables.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = $this->getVersionedTables('game_results_v%');

        foreach ($tables as $tableName) {
            if ($this->hasIndex($tableName, 'idx_mode_mmr_char')) {
                DB::statement("ALTER TABLE `{$tableName}` DROP INDEX `idx_mode_mmr_char`");
            }

            if ($this->hasIndex($tableName, 'idx_mode_char_weapon')) {
                DB::statement("ALTER TABLE `{$tableName}` DROP INDEX `idx_mode_char_weapon`");
            }
        }
    }

    /**
     * 버전별 테이블 목록 가져오기
     */
    private function getVersionedTables(string $pattern): array
    {
        $databaseName = env('DB_DATABASE');
        $tables = DB::select("SHOW TABLES LIKE ?", [$pattern]);

        $columnName = "Tables_in_{$databaseName}";
        $tableNames = [];

        foreach ($tables as $table) {
            $tableNames[] = $table->$columnName;
        }

        return $tableNames;
    }

    /**
     * 인덱스 존재 여부 확인
     */
    private function hasIndex(string $tableName, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$tableName}` WHERE Key_name = ?", [$indexName]);
        return !empty($indexes);
    }
};