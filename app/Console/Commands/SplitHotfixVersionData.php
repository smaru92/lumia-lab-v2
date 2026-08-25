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
            $columns = $this->sharedColumns($sourceOrders, $targetOrders);
            $columnList = '`' . implode('`, `', $columns) . '`';
            $selectList = 'o.`' . implode('`, o.`', $columns) . '`';

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

        DB::affectingStatement(
            "INSERT INTO `{$targetResults}` ({$resultColumnList})
             SELECT {$resultColumnList} FROM `{$sourceResults}` WHERE start_at >= ?",
            [$cut]
        );
        DB::affectingStatement(
            "DELETE FROM `{$sourceResults}` WHERE start_at >= ?",
            [$cut]
        );

        $this->info("  - game_results: " . number_format($count) . '건 이동 완료');
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
