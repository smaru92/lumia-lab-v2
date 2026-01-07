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

        $latestVersion = VersionHistory::latest('created_at')->first();
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

        Log::channel($this->logChannel)->info("Using versioned table: {$versionedTableName}");

        // 테이블 존재 확인 및 생성
        $this->ensureTableExists($versionedTableName);

        $summaryModel = $this->getSummaryModel();

        // 트랜잭션으로 delete와 insert를 묶어서 처리
        DB::beginTransaction();

        try {
            // 1단계: 버전별 테이블이므로 TRUNCATE로 빠르게 삭제
            Log::channel($this->logChannel)->info('Truncating table...');
            DB::table($versionedTableName)->truncate();
            Log::channel($this->logChannel)->info("Truncated table {$versionedTableName}");

            // 2단계: 데이터 처리하면서 바로 insert (메모리에 모두 쌓지 않음)
            $insertChunkSize = 500;
            $totalInserted = 0;
            $batchData = [];

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
                        DB::table($versionedTableName)->insert($batchData);
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
                DB::table($versionedTableName)->insert($batchData);
                $totalInserted += count($batchData);
            }

            DB::commit();

            Log::channel($this->logChannel)->info("Inserted {$totalInserted} new records");
            Log::channel($this->logChannel)->info('E: update summary');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel($this->logChannel)->error('Error: ' . $e->getMessage());
            Log::channel($this->logChannel)->error($e->getTraceAsString());
            throw $e;
        } finally {
            // 메모리 정리
            gc_collect_cycles();
        }
    }
}
