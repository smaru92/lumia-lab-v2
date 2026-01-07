<?php

namespace App\Services;

use App\Models\GameResultTraitMainSummary;
use App\Models\VersionHistory;
use App\Traits\ErDevTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GameResultTraitMainSummaryService
{
    use ErDevTrait;
    protected RankRangeService $rankRangeService;
    protected GameResultService $gameResultService;
    protected VersionedGameTableManager $versionedTableManager;

    public function __construct()
    {
        $this->rankRangeService = new RankRangeService();
        $this->gameResultService = new GameResultService();
        $this->versionedTableManager = new VersionedGameTableManager();
    }

    protected function getVersionedTableName(array $filters): string
    {
        $versionSeason = $filters['version_season'] ?? null;
        $versionMajor = $filters['version_major'] ?? null;
        $versionMinor = $filters['version_minor'] ?? null;

        if (!$versionSeason || !$versionMajor || !$versionMinor) {
            $latestVersion = VersionHistory::latest('created_at')->first();
            $versionSeason = $versionSeason ?? $latestVersion->version_season;
            $versionMajor = $versionMajor ?? $latestVersion->version_major;
            $versionMinor = $versionMinor ?? $latestVersion->version_minor;
        }

        return VersionedGameTableManager::getTableName('game_results_trait_main_summary', [
            'version_season' => $versionSeason,
            'version_major' => $versionMajor,
            'version_minor' => $versionMinor,
        ]);
    }

    /**
     * 게임 결과 특성 메인 요약 데이터 갱신
     * @return void
     */
    public function updateGameResultTraitMainSummary($versionSeason = null, $versionMajor = null, $versionMinor = null)
    {
        Log::channel('updateGameResultTraitMainSummary')->info('S: game trait main result summary');

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
        $tableName = VersionedGameTableManager::getTableName('game_results_trait_main_summary', $versionFilters);

        Log::channel('updateGameResultTraitMainSummary')->info("Using versioned table: {$tableName}");

        // 테이블 존재 확인 및 생성
        $this->versionedTableManager->ensureGameResultTraitMainSummaryTableExists($tableName);

        $tiers = $this->tierRange;

        // TRUNCATE는 DDL이므로 트랜잭션 밖에서 실행 (암묵적 커밋 방지)
        Log::channel('updateGameResultTraitMainSummary')->info('Truncating table...');
        DB::table($tableName)->truncate();
        Log::channel('updateGameResultTraitMainSummary')->info("Truncated table {$tableName}");

        // 데이터 처리하면서 바로 insert
        $insertChunkSize = 500;
        $totalInserted = 0;
        $batchData = [];

        try {
            foreach ($tiers as $tier) {
                $minScore = $this->rankRangeService->getMinScore($tier['tier'], $tier['tierNumber'], $versionFilters) ?: 0;
                $minTier = $tier['tier'].$tier['tierNumber'];
                echo $tier['tier'] . $tier['tierNumber'] . ':' . $minScore . "\n";

                $startTime = microtime(true);

                $gameResults = $this->gameResultService->getGameResultTraitMain([
                    'version_season' => $versionSeason,
                    'version_major' => $versionMajor,
                    'version_minor' => $versionMinor,
                    'min_tier' => $minTier,
                    'min_score' => $minScore,
                ]);

                $queryTime = round((microtime(true) - $startTime) * 1000, 2);
                Log::channel('updateGameResultTraitMainSummary')->info("Query time for {$minTier}: {$queryTime}ms");

                $gameResultsCursor = $gameResults['data'];

                foreach ($gameResultsCursor as $gameResult) {
                    $batchData[] = [
                        'trait_id' => $gameResult['traitId'],
                        'trait_name' => $gameResult['name'],
                        'is_main' => $gameResult['isMain'],
                        'meta_tier' => $gameResult['metaTier'],
                        'meta_score' => $gameResult['metaScore'],
                        'game_count' => $gameResult['gameCount'],
                        'min_tier' => $minTier,
                        'min_score' => $minScore,
                        'positive_game_count' => $gameResult['positiveGameCount'],
                        'negative_game_count' => $gameResult['negativeGameCount'],
                        'game_count_percent' => $gameResult['gameCountPercent'],
                        'positive_game_count_percent' => $gameResult['positiveGameCountPercent'],
                        'negative_game_count_percent' => $gameResult['negativeGameCountPercent'],
                        'top1_count' => $gameResult['top1Count'],
                        'top2_count' => $gameResult['top2Count'],
                        'top4_count' => $gameResult['top4Count'],
                        'top1_count_percent' => $gameResult['top1CountPercent'],
                        'top2_count_percent' => $gameResult['top2CountPercent'],
                        'top4_count_percent' => $gameResult['top4CountPercent'],
                        'endgame_win_percent' => $gameResult['endgameWinPercent'],
                        'avg_mmr_gain' => $gameResult['avgMmrGain'],
                        'avg_team_kill_score' => $gameResult['avgTeamKillScore'],
                        'positive_avg_mmr_gain' => $gameResult['positiveAvgMmrGain'],
                        'negative_avg_mmr_gain' => $gameResult['negativeAvgMmrGain'],
                        'updated_at' => now(),
                        'created_at' => now(),
                    ];

                    // 일정 크기마다 insert
                    if (count($batchData) >= $insertChunkSize) {
                        DB::table($tableName)->insert($batchData);
                        $totalInserted += count($batchData);
                        $batchData = [];

                        // 메모리 정리
                        if ($totalInserted % 5000 === 0) {
                            gc_collect_cycles();
                        }
                    }
                }

                // 티어별 처리 후 메모리 정리
                unset($gameResults, $gameResultsCursor);
                gc_collect_cycles();
            }

            // 남은 데이터 insert
            if (!empty($batchData)) {
                DB::table($tableName)->insert($batchData);
                $totalInserted += count($batchData);
            }

            Log::channel('updateGameResultTraitMainSummary')->info("Inserted {$totalInserted} new records");
            Log::channel('updateGameResultTraitMainSummary')->info('E: game trait main result summary');
        } catch (\Exception $e) {
            Log::channel('updateGameResultTraitMainSummary')->error('Error: ' . $e->getMessage());
            Log::channel('updateGameResultTraitMainSummary')->error($e->getTraceAsString());
            throw $e;
        } finally {
            // 메모리 정리
            gc_collect_cycles();
        }
    }

    /**
     * 특성 메인 통계 리스트 조회
     * @param array $filters
     * @return \Illuminate\Support\Collection
     */
    public function getList(array $filters)
    {
        $tableName = $this->getVersionedTableName($filters);
        unset($filters['version_season'], $filters['version_major'], $filters['version_minor']);

        $results = DB::table($tableName . ' as gtms')
            ->select(
                'gtms.*',
                'traits.category as trait_category',
                'traits.tooltip as trait_tooltip'
            )
            ->join('traits', 'traits.id', '=', 'gtms.trait_id')
            ->where($filters)
            ->orderBy('meta_score', 'desc')
            ->get();

        return $results;
    }
}
