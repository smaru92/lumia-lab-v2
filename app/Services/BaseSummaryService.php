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
        $allInsertData = []; // 모든 insert 데이터를 임시로 모음

        // 트랜잭션 밖에서 데이터 수집 (기존 데이터는 그대로 유지)
        try {
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
                    $allInsertData[] = $this->transformData($gameResult, $minTier, $minScore, $versionSeason, $versionMajor, $versionMinor);
                }

                // 티어별 처리 후 메모리 정리
                unset($gameResults);
                gc_collect_cycles();
            }

            // 트랜잭션 시작: 빠르게 삭제 후 insert
            DB::beginTransaction();

            // 기존 데이터 삭제
            $summaryModel::where('version_season', $versionSeason)
                ->where('version_major', $versionMajor)
                ->where('version_minor', $versionMinor)
                ->delete();

            // 새 데이터를 chunk로 insert
            $chunkSize = 50;
            foreach (array_chunk($allInsertData, $chunkSize) as $chunk) {
                $summaryModel::insert($chunk);
            }

            DB::commit();
            Log::channel($this->logChannel)->info('E: update summary');
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::channel($this->logChannel)->error('Error: ' . $e->getMessage());
            Log::channel($this->logChannel)->error($e->getTraceAsString());
            throw $e;
        } finally {
            // 메모리 정리
            unset($allInsertData);
            gc_collect_cycles();
        }
    }
}
