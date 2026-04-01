<?php

namespace App\Services;

use App\Traits\ErDevTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\VersionHistory;

abstract class BaseSummaryService
{
    use ErDevTrait;

    protected RankRangeService $rankRangeService;
    protected GameResultService $gameResultService;
    protected VersionedGameTableManager $versionedTableManager;
    protected string $logChannel;

    public function __construct(string $logChannel)
    {
        $this->rankRangeService = new RankRangeService();
        $this->gameResultService = new GameResultService();
        $this->versionedTableManager = new VersionedGameTableManager();
        $this->logChannel = $logChannel;
    }

    abstract protected function getSummaryModel(): string;
    abstract protected function getSummaryTableBaseName(): string;
    abstract protected function getGameResults(array $params): iterable;
    abstract protected function transformData(object|array $gameResult, string $minTier, int $minScore): array;
    abstract protected function ensureTableExists(string $tableName): void;

    public function updateSummary($versionSeason = null, $versionMajor = null, $versionMinor = null)
    {
        Log::channel($this->logChannel)->info('S: update summary');

        $latestVersion = VersionHistory::active()->latest('created_at')->first();
        $versionSeason = $versionSeason ?? $latestVersion->version_season;
        $versionMajor = $versionMajor ?? $latestVersion->version_major;
        $versionMinor = $versionMinor ?? $latestVersion->version_minor;

        // 버전별 테이블명 생성
        $versionFilters = [
            'version_season' => $versionSeason,
            'version_major' => $versionMajor,
            'version_minor' => $versionMinor
        ];
        $versionedTableName = VersionedGameTableManager::getTableName(
            $this->getSummaryTableBaseName(),
            $versionFilters
        );
        $shadowTableName = $versionedTableName . '_new';

        Log::channel($this->logChannel)->info("Using versioned table: {$versionedTableName}");

        // 원본 테이블 존재 확인 및 생성 (최초 실행 시)
        $this->ensureTableExists($versionedTableName);

        // 섀도 테이블 생성 (원본 테이블 구조 복사)
        // LIKE는 DDL이므로 트랜잭션 밖에서 실행
        DB::statement("DROP TABLE IF EXISTS `{$shadowTableName}`");
        DB::statement("CREATE TABLE `{$shadowTableName}` LIKE `{$versionedTableName}`");
        Log::channel($this->logChannel)->info("Created shadow table: {$shadowTableName}");

        // 데이터 처리하면서 섀도 테이블에 insert (원본 테이블은 변경 없음)
        $insertChunkSize = 500;
        $totalInserted = 0;
        $batchData = [];

        try {
            foreach ($this->tierRange as $tier) {
                $minScore = $this->rankRangeService->getMinScore($tier['tier'], $tier['tierNumber'], $versionFilters) ?: 0;
                echo $tier['tier'] . $tier['tierNumber'] . ':' . $minScore . "\n";
                $minTier = $tier['tier'] . $tier['tierNumber'];

                $gameResults = $this->getGameResults([
                    'version_season' => $versionSeason,
                    'version_major' => $versionMajor,
                    'version_minor' => $versionMinor,
                    'min_tier' => $minTier,
                    'min_score' => $minScore,
                ]);

                foreach ($gameResults as $gameResult) {
                    $batchData[] = $this->transformData($gameResult, $minTier, $minScore);

                    // 일정 크기마다 insert
                    if (count($batchData) >= $insertChunkSize) {
                        DB::table($shadowTableName)->insert($batchData);
                        $totalInserted += count($batchData);
                        $batchData = [];

                        // 메모리 정리
                        if ($totalInserted % 5000 === 0) {
                            gc_collect_cycles();
                        }
                    }
                }

                // 티어별 처리 후 메모리 정리
                unset($gameResults);
                gc_collect_cycles();
            }

            // 남은 데이터 insert
            if (!empty($batchData)) {
                DB::table($shadowTableName)->insert($batchData);
                $totalInserted += count($batchData);
            }

            Log::channel($this->logChannel)->info("Inserted {$totalInserted} new records into shadow table");

            // 원자적 테이블 교체: 사용자에게 데이터 공백 없이 교체됨
            $oldTableName = $versionedTableName . '_old';
            DB::statement("DROP TABLE IF EXISTS `{$oldTableName}`");
            DB::statement("RENAME TABLE `{$versionedTableName}` TO `{$oldTableName}`, `{$shadowTableName}` TO `{$versionedTableName}`");
            DB::statement("DROP TABLE IF EXISTS `{$oldTableName}`");

            Log::channel($this->logChannel)->info("Swapped shadow table to production: {$versionedTableName}");
            Log::channel($this->logChannel)->info('E: update summary');
        } catch (\Exception $e) {
            // 실패 시 섀도 테이블 정리
            DB::statement("DROP TABLE IF EXISTS `{$shadowTableName}`");
            Log::channel($this->logChannel)->error('Error: ' . $e->getMessage());
            Log::channel($this->logChannel)->error($e->getTraceAsString());
            throw $e;
        } finally {
            // 메모리 정리
            gc_collect_cycles();
        }
    }
}
