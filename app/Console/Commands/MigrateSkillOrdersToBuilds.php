<?php

namespace App\Console\Commands;

use App\Services\GameResultSkillOrderSummaryService;
use App\Services\VersionedGameTableManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 예전 game_result_skill_orders_v* (게임당 ~16행) 를 game_result_skill_builds_v* (게임당 1행) 로 옮긴다.
 *
 * 수집기는 이제 skill_builds 에 바로 쓰므로 skill_orders 는 더 이상 쌓이지 않는다.
 * 이 명령은 그 전에 쌓인 행을 옮기는 일회성 정리용이다. (버전당 2.3G -> 수백 MB)
 * 이미 있는 행은 덮어쓰므로 여러 번 실행해도 안전하다.
 */
class MigrateSkillOrdersToBuilds extends Command
{
    protected $signature = 'skill-builds:migrate-orders
                            {versions?* : 대상 버전 (예: 12.6.0 12.5.0). 없으면 skill_orders 테이블이 남은 모든 버전}
                            {--drop : 옮긴 게임 수가 맞으면 skill_orders 테이블을 삭제한다}';

    protected $description = '스킬 순서 원본(skill_orders)을 게임별 1행(skill_builds)으로 옮긴다';

    /** skill_orders 를 한 번에 읽을 id 구간 */
    private const CHUNK = 500000;

    /** 구간 경계에 걸친 플레이어의 나머지 행을 찾을 범위 (플레이어 1명 = 20여 행) */
    private const TAIL_WINDOW = 5000;

    public function handle(VersionedGameTableManager $tableManager): int
    {
        $tables = $this->targetTables();
        if ($tables === null) {
            return self::FAILURE;
        }
        if (empty($tables)) {
            $this->info('옮길 skill_orders 테이블이 없습니다.');
            return self::SUCCESS;
        }

        foreach ($tables as $orderTable) {
            $buildTable = str_replace('game_result_skill_orders_', 'game_result_skill_builds_', $orderTable);
            $this->line("{$orderTable} -> {$buildTable}");

            $tableManager->ensureGameResultSkillBuildTableExists($buildTable);
            $this->copy($orderTable, $buildTable);

            if (!$this->verify($orderTable, $buildTable)) {
                $this->error('  게임 수가 맞지 않아 skill_orders 를 남겨 둡니다.');
                continue;
            }

            if ($this->option('drop')) {
                Schema::drop($orderTable);
                $this->info("  {$orderTable} 삭제");
            }
        }

        return self::SUCCESS;
    }

    /**
     * @return string[]|null 버전 인자가 잘못되면 null
     */
    private function targetTables(): ?array
    {
        $versions = $this->argument('versions');

        if (empty($versions)) {
            return collect(DB::select("SHOW TABLES LIKE 'game_result_skill_orders_v%'"))
                ->map(fn ($row) => array_values((array) $row)[0])
                ->sort()
                ->values()
                ->all();
        }

        $tables = [];
        foreach ($versions as $version) {
            // parse_version_key 는 잘못된 값을 기본 버전으로 바꿔 버리므로 검증만 하는 쪽을 쓴다
            $filters = _parse_version_key_raw($version);
            if ($filters === null) {
                $this->error("버전 형식이 잘못되었습니다: {$version}");
                return null;
            }

            $table = VersionedGameTableManager::getTableName('game_result_skill_orders', $filters);
            if (!Schema::hasTable($table)) {
                $this->warn("{$table} 테이블이 없어 건너뜁니다.");
                continue;
            }
            $tables[] = $table;
        }

        return $tables;
    }

    /**
     * skill_orders 를 PK 구간으로 읽어 플레이어별로 접는다. (game_result_id 인덱스가 없다)
     * 수집기는 플레이어 1명의 스킬 행을 한 번에 넣었으므로 id 순서 = 레벨 순서다.
     */
    private function copy(string $orderTable, string $buildTable): void
    {
        $maxId = (int) DB::table($orderTable)->max('id');
        $cursor = (int) DB::table($orderTable)->min('id') - 1;
        $orderLength = GameResultSkillOrderSummaryService::SKILL_ORDER_MAX_LENGTH;
        $idsLength = GameResultSkillOrderSummaryService::SKILL_IDS_MAX_LENGTH;

        while ($cursor < $maxId) {
            $end = min($cursor + self::CHUNK, $maxId);

            // 구간 끝에 걸친 플레이어는 나머지 행까지 끌어와 한 구간에서 끝낸다. (덮어쓰기라 쪼개지면 안 된다)
            if ($end < $maxId) {
                $lastGameResultId = DB::table($orderTable)->where('id', '<=', $end)->orderByDesc('id')->value('game_result_id');
                $tailEnd = DB::table($orderTable)
                    ->where('id', '>', $end)
                    ->where('id', '<=', $end + self::TAIL_WINDOW)
                    ->where('game_result_id', $lastGameResultId)
                    ->max('id');
                if ($tailEnd) {
                    $end = (int) $tailEnd;
                }
            }

            // 슬롯 = skill_id 백의 자리 (GameResultSkillOrderSummaryService::makeSkillBuild 와 같은 규칙)
            DB::affectingStatement(
                "INSERT INTO `{$buildTable}` (game_result_id, skill_order, skill_ids)
                 SELECT game_result_id,
                        LEFT(GROUP_CONCAT((skill_id DIV 100) % 10 ORDER BY order_level SEPARATOR ''), {$orderLength}),
                        LEFT(GROUP_CONCAT(skill_id ORDER BY order_level SEPARATOR ','), {$idsLength})
                 FROM `{$orderTable}`
                 WHERE id > ? AND id <= ?
                 GROUP BY game_result_id
                 ON DUPLICATE KEY UPDATE
                    skill_order = VALUES(skill_order),
                    skill_ids = VALUES(skill_ids)",
                [$cursor, $end]
            );

            $this->line("  ~{$end} / {$maxId}");
            $cursor = $end;
        }
    }

    private function verify(string $orderTable, string $buildTable): bool
    {
        $orderGames = (int) DB::table($orderTable)->distinct()->count('game_result_id');
        $missing = (int) DB::table($buildTable)->whereNull('skill_ids')->count();
        $buildGames = (int) DB::table($buildTable)->count();

        // skill_builds 에는 이 명령 이후 수집된 게임도 있으므로 같거나 많아야 한다
        $this->line("  skill_orders 게임 수 " . number_format($orderGames)
            . " / skill_builds " . number_format($buildGames) . " (skill_ids 비어 있음 " . number_format($missing) . ")");

        return $missing === 0 && $buildGames >= $orderGames;
    }
}
