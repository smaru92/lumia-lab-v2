<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 버전별 game_result_trait_orders 의 (game_result_id, trait_id) 인덱스 삭제
     *
     * idx_grt_game_result_id_trait_main (game_result_id, trait_id, is_main) 의 앞부분과 같아서
     * 같은 쿼리를 그 인덱스로 처리할 수 있다. 버전당 약 0.4G.
     * (파일 크기는 그대로고 빈 공간이 이후 삽입에 재사용된다)
     */
    public function up(): void
    {
        foreach ($this->tables() as $tableName) {
            if ($this->hasIndex($tableName, 'idx_grt_game_result_trait')
                && $this->hasIndex($tableName, 'idx_grt_game_result_id_trait_main')) {
                DB::statement("ALTER TABLE `{$tableName}` DROP INDEX `idx_grt_game_result_trait`, ALGORITHM=INPLACE, LOCK=NONE");
                echo "Dropped idx_grt_game_result_trait from {$tableName}\n";
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables() as $tableName) {
            if (!$this->hasIndex($tableName, 'idx_grt_game_result_trait')) {
                DB::statement("CREATE INDEX `idx_grt_game_result_trait` ON `{$tableName}` (`game_result_id`, `trait_id`)");
            }
        }
    }

    private function tables(): array
    {
        return array_map(
            fn ($row) => array_values((array) $row)[0],
            DB::select("SHOW TABLES LIKE 'game_result_trait_orders_v%'")
        );
    }

    private function hasIndex(string $tableName, string $indexName): bool
    {
        return !empty(DB::select("SHOW INDEX FROM `{$tableName}` WHERE Key_name = '{$indexName}'"));
    }
};
