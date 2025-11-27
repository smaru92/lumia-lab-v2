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
    protected string $logChannel;

    public function __construct(string $logChannel)
    {
        $this->rankRangeService = new RankRangeService();
        $this->gameResultService = new GameResultService();
        $this->logChannel = $logChannel;
    }

    abstract protected function getSummaryModel(): string;
    abstract protected function getGameResults(array $params): iterable;
    abstract protected function transformData(object|array $gameResult, string $minTier, int $minScore, int $versionSeason, int $versionMajor, int $versionMinor): array;

    public function updateSummary($versionSeason = null, $versionMajor = null, $versionMinor = null)
    {
        Log::channel($this->logChannel)->info('S: update summary');

        $latestVersion = VersionHistory::latest('created_at')->first();
        $versionSeason = $versionSeason ?? $latestVersion->version_season;
        $versionMajor = $versionMajor ?? $latestVersion->version_major;
        $versionMinor = $versionMinor ?? $latestVersion->version_minor;

        $summaryModel = $this->getSummaryModel();

        try {
            // 1단계: 기존 데이터 삭제 (청크 단위)
            $deleteChunkSize = 5000;
            $deletedCount = 0;

            Log::channel($this->logChannel)->info('Deleting old records...');
            do {
                $deleted = $summaryModel::where('version_season', $versionSeason)
                    ->where('version_major', $versionMajor)
                    ->where('version_minor', $versionMinor)
                    ->limit($deleteChunkSize)
                    ->delete();

                $deletedCount += $deleted;

                // 메모리 정리
                if ($deletedCount % 20000 === 0) {
                    gc_collect_cycles();
                }
            } while ($deleted > 0);

            Log::channel($this->logChannel)->info("Deleted {$deletedCount} old records");

            // 2단계: 데이터 처리하면서 바로 insert (메모리에 모두 쌓지 않음)
            $insertChunkSize = 500;
            $totalInserted = 0;
            $batchData = [];

            foreach ($this->tierRange as $tier) {
                $versionFilters = [
                    'version_season' => $versionSeason,
                    'version_major' => $versionMajor,
                    'version_minor' => $versionMinor
                ];
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
                    $batchData[] = $this->transformData($gameResult, $minTier, $minScore, $versionSeason, $versionMajor, $versionMinor);

                    // 일정 크기마다 insert
                    if (count($batchData) >= $insertChunkSize) {
                        $summaryModel::insert($batchData);
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
                $summaryModel::insert($batchData);
                $totalInserted += count($batchData);
            }

            Log::channel($this->logChannel)->info("Inserted {$totalInserted} new records");
            Log::channel($this->logChannel)->info('E: update summary');
        } catch (\Exception $e) {
            Log::channel($this->logChannel)->error('Error: ' . $e->getMessage());
            Log::channel($this->logChannel)->error($e->getTraceAsString());
            throw $e;
        } finally {
            // 메모리 정리
            gc_collect_cycles();
        }
    }
}
