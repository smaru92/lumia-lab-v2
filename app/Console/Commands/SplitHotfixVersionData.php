<?php

namespace App\Console\Commands;

use App\Models\VersionHistory;
use App\Services\VersionedGameTableManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 핫픽스 버전으로 전적 데이터를 분리한다.
 *
 * ER API 는 핫픽스를 내려주지 않으므로 수집 당시에는 모두 기본 버전 테이블
 * (예: game_results_v12_2_0)에 들어간다. 핫픽스를 뒤늦게 등록한 경우
 * 게임 시작 시각을 기준으로 핫픽스 테이블(game_results_v12_2_0b)로 옮긴다.
 *
 * 수집 로직 자체는 이미 핫픽스를 판정하므로, 이 명령은 핫픽스 등록 이전에
 * 쌓인 데이터를 정리하는 용도다. 여러 번 실행해도 안전하다.
 */
class SplitHotfixVersionData extends Command
{
    protected $signature = 'versions:split-hotfix {--dry-run : 실제로 옮기지 않고 건수만 출력}';

    protected $description = '핫픽스 시작 시각 기준으로 전적 데이터를 핫픽스 버전 테이블로 분리';

    /** 전적 본체와 함께 옮겨야 하는 하위 테이블 (game_result_id 로 연결) */
    private const ORDER_TABLES = [
        'game_result_skill_orders' => 'ensureGameResultSkillOrderTableExists',
        'game_result_equipment_orders' => 'ensureGameResultEquipmentOrderTableExists',
        'game_result_first_equipment_orders' => 'ensureGameResultFirstEquipmentOrderTableExists',
        'game_result_trait_orders' => 'ensureGameResultTraitOrderTableExists',
    ];

    public function handle(VersionedGameTableManager $tableManager): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $hotfixVersions = VersionHistory::query()
            ->whereNotNull('version_hotfix')
            ->where('version_hotfix', '!=', '')
            ->orderBy('version_season')
            ->orderBy('version_major')
            ->orderBy('version_minor')
            ->orderBy('start_date')
            ->get();

        if ($hotfixVersions->isEmpty()) {
            $this->info('핫픽스가 등록된 버전이 없습니다.');
            return self::SUCCESS;
        }

        foreach ($hotfixVersions as $version) {
            $this->splitVersion($tableManager, $version, $dryRun);
        }

        if ($dryRun) {
            $this->warn('dry-run 모드였습니다. 실제로 옮기려면 --dry-run 없이 실행하세요.');
        }

        return self::SUCCESS;
    }

    private function splitVersion(VersionedGameTableManager $tableManager, VersionHistory $version, bool $dryRun): void
    {
        $source = $this->findSourceVersion($version);

        if (!$source) {
            $this->warn("[{$version->version_key}] 직전 버전을 찾지 못해 건너뜁니다.");
            return;
        }

        $sourceResults = VersionedGameTableManager::getTableName('game_results', $source->version_filters);
        $targetResults = VersionedGameTableManager::getTableName('game_results', $version->version_filters);

        if (!Schema::hasTable($sourceResults)) {
            $this->warn("[{$version->version_key}] 원본 테이블이 없습니다: {$sourceResults}");
            return;
        }

        $cut = $version->start_date->format('Y-m-d H:i:s');
        $count = DB::table($sourceResults)->where('start_at', '>=', $cut)->count();

        $this->line("[{$version->version_key}] {$sourceResults} -> {$targetResults} (start_at >= {$cut}) : "
            . number_format($count) . '건');

        if ($count === 0 || $dryRun) {
            return;
        }

        $tableManager->ensureGameResultTableExists($targetResults);

        // 원본과 대상 테이블의 auto_increment 는 서로 독립적으로 진행되므로 id 가 겹칠 수 있다.
        // (핫픽스 등록 후 새 코드가 대상 테이블에 직접 쓰기 시작하면 반드시 발생한다)
        // 겹치지 않도록 이동하는 id 를 통째로 밀어 올린다. 단조 증가라 참조 관계는 그대로 유지된다.
        $offset = $this->reserveIdRange($sourceResults, $targetResults, $cut);

        if ($offset > 0) {
            $this->line("  - id 충돌 회피를 위해 +{$offset} 만큼 이동");
        }

        // 하위 테이블부터 옮긴다. (원본 game_results 가 아직 남아 있어야 조인이 된다)
        foreach (self::ORDER_TABLES as $baseName => $ensureMethod) {
            $sourceOrders = VersionedGameTableManager::getTableName($baseName, $source->version_filters);
            $targetOrders = VersionedGameTableManager::getTableName($baseName, $version->version_filters);

            if (!Schema::hasTable($sourceOrders)) {
                continue;
            }

            $tableManager->$ensureMethod($targetOrders);

            // 컬럼 순서에 의존하지 않도록 컬럼명을 명시한다.
            // (기존 테이블은 ALTER 로, 신규 테이블은 CREATE 로 만들어져 순서가 다를 수 있다)
            // 하위 테이블의 id 는 아무도 참조하지 않으므로 대상 테이블이 새로 발급하게 둔다.
            $columns = array_values(array_diff(
                $this->sharedColumns($sourceOrders, $targetOrders),
                ['id']
            ));

            $columnList = '`' . implode('`, `', $columns) . '`';
            $selectList = implode(', ', array_map(
                fn (string $column) => $column === 'game_result_id'
                    ? "o.`game_result_id` + {$offset}"
                    : "o.`{$column}`",
                $columns
            ));

            $moved = DB::affectingStatement(
                "INSERT INTO `{$targetOrders}` ({$columnList})
                 SELECT {$selectList} FROM `{$sourceOrders}` o
                 JOIN `{$sourceResults}` r ON r.id = o.game_result_id
                 WHERE r.start_at >= ?",
                [$cut]
            );

            DB::affectingStatement(
                "DELETE o FROM `{$sourceOrders}` o
                 JOIN `{$sourceResults}` r ON r.id = o.game_result_id
                 WHERE r.start_at >= ?",
                [$cut]
            );

            $this->line("  - {$baseName}: " . number_format($moved) . '건 이동');
        }

        $resultColumns = $this->sharedColumns($sourceResults, $targetResults);
        $resultColumnList = '`' . implode('`, `', $resultColumns) . '`';
        $resultSelectList = implode(', ', array_map(
            fn (string $column) => $column === 'id' ? "`id` + {$offset}" : "`{$column}`",
            $resultColumns
        ));

        DB::affectingStatement(
            "INSERT INTO `{$targetResults}` ({$resultColumnList})
             SELECT {$resultSelectList} FROM `{$sourceResults}` WHERE start_at >= ?",
            [$cut]
        );
        DB::affectingStatement(
            "DELETE FROM `{$sourceResults}` WHERE start_at >= ?",
            [$cut]
        );

        $this->info("  - game_results: " . number_format($count) . '건 이동 완료');
    }

    /**
     * 이동할 id 구간을 대상 테이블에 예약하고, 필요한 offset 을 반환한다.
     *
     * 수집기가 대상 테이블에 실시간으로 쓰고 있으므로 단순히 "현재 최대 id + 1" 로 밀면
     * 옮기는 도중 들어온 행과 다시 부딪힌다. 그래서 옮길 구간 뒤로 AUTO_INCREMENT 를
     * 미리 올려두어(예약) 동시 삽입이 우리 구간을 침범하지 못하게 한다.
     */
    private function reserveIdRange(string $sourceResults, string $targetResults, string $cut): int
    {
        $pending = DB::table($sourceResults)->where('start_at', '>=', $cut);
        $sourceMin = (int) (clone $pending)->min('id');
        $sourceMax = (int) (clone $pending)->max('id');

        if ($sourceMin === 0) {
            return 0;
        }

        $targetNextId = $this->nextAutoIncrement($targetResults);

        // 이동 구간이 이미 대상 테이블의 발급 구간보다 뒤라면 그대로 옮겨도 안전하다.
        $offset = $targetNextId > $sourceMin ? ($targetNextId - $sourceMin) : 0;

        // 예약: 옮길 구간의 마지막 id 보다 뒤에서 다음 id 가 발급되도록 한다.
        // 조회와 ALTER 사이에 들어온 행을 감안해 여유분을 둔다.
        $reserveFrom = $sourceMax + $offset + 1000;
        DB::statement("ALTER TABLE `{$targetResults}` AUTO_INCREMENT = {$reserveFrom}");

        return $offset;
    }

    /**
     * 테이블이 다음에 발급할 auto_increment 값
     */
    private function nextAutoIncrement(string $table): int
    {
        $row = DB::selectOne(
            'SELECT AUTO_INCREMENT AS next_id FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$table]
        );

        $next = (int) ($row->next_id ?? 0);

        // 통계가 비어 있을 수 있으므로 실제 최대 id 로 보정
        return max($next, ((int) DB::table($table)->max('id')) + 1);
    }

    /**
     * 두 테이블에 공통으로 존재하는 컬럼 목록
     */
    private function sharedColumns(string $sourceTable, string $targetTable): array
    {
        $source = Schema::getColumnListing($sourceTable);
        $target = Schema::getColumnListing($targetTable);

        return array_values(array_intersect($source, $target));
    }

    /**
     * 핫픽스 직전 버전(같은 season.major.minor 중 start_date 가 바로 앞선 것)을 찾는다.
     */
    private function findSourceVersion(VersionHistory $version): ?VersionHistory
    {
        return VersionHistory::query()
            ->where('version_season', $version->version_season)
            ->where('version_major', $version->version_major)
            ->where('version_minor', $version->version_minor)
            ->where('start_date', '<', $version->start_date)
            ->orderByDesc('start_date')
            ->first();
    }
}
