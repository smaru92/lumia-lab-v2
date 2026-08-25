<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 전적 수집 시점의 특성 슬롯(main/sub1/sub2)을 기록한다.
     * 특성의 그룹은 패치마다 바뀔 수 있으므로 traits 마스터를 조인하면 과거 통계가 흔들린다.
     * 기본 테이블과 이미 생성된 버전별 테이블 모두에 컬럼을 추가한다.
     */
    public function up(): void
    {
        foreach ($this->traitOrderTables() as $tableName) {
            if (Schema::hasColumn($tableName, 'trait_slot')) {
                continue;
            }

            // 컬럼을 맨 뒤에 붙여야 MariaDB 가 ALGORITHM=INSTANT 로 처리한다.
            // AFTER 로 위치를 지정하면 1천만 행 테이블이 통째로 재작성되면서 오래 락이 걸린다.
            DB::statement("ALTER TABLE `{$tableName}` ADD COLUMN `trait_slot` VARCHAR(10) NULL COMMENT '특성 슬롯(main/sub1/sub2)'");
        }
    }

    public function down(): void
    {
        foreach ($this->traitOrderTables() as $tableName) {
            if (!Schema::hasColumn($tableName, 'trait_slot')) {
                continue;
            }

            DB::statement("ALTER TABLE `{$tableName}` DROP COLUMN `trait_slot`");
        }
    }

    /**
     * 기본 테이블 + 버전별 테이블 목록
     */
    private function traitOrderTables(): array
    {
        $tables = [];

        if (Schema::hasTable('game_result_trait_orders')) {
            $tables[] = 'game_result_trait_orders';
        }

        foreach (DB::select("SHOW TABLES LIKE 'game_result_trait_orders_v%'") as $table) {
            $tables[] = array_values((array) $table)[0];
        }

        return $tables;
    }
};
