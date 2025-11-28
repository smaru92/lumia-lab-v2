<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 버전별 game_result_trait_orders 테이블에 인덱스 추가
     * GROUP_CONCAT 쿼리 성능 최적화용
     */
    public function up(): void
    {
        // 버전별 테이블 목록 조회
        $tables = DB::select("SHOW TABLES LIKE 'game_result_trait_orders_v%'");

        foreach ($tables as $table) {
            $tableName = array_values((array)$table)[0];

            // 인덱스가 이미 존재하는지 확인
            $indexExists = DB::select("SHOW INDEX FROM `{$tableName}` WHERE Key_name = 'idx_grt_game_result_trait'");

            if (empty($indexExists)) {
                DB::statement("CREATE INDEX `idx_grt_game_result_trait` ON `{$tableName}` (`game_result_id`, `trait_id`)");
                echo "Added index to {$tableName}\n";
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = DB::select("SHOW TABLES LIKE 'game_result_trait_orders_v%'");

        foreach ($tables as $table) {
            $tableName = array_values((array)$table)[0];

            $indexExists = DB::select("SHOW INDEX FROM `{$tableName}` WHERE Key_name = 'idx_grt_game_result_trait'");

            if (!empty($indexExists)) {
                DB::statement("DROP INDEX `idx_grt_game_result_trait` ON `{$tableName}`");
            }
        }
    }
};