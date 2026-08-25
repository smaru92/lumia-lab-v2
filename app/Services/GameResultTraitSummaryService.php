<?php

namespace App\Services;

use App\Models\GameResultTraitSummary;
use App\Models\VersionHistory;
use App\Traits\ErDevTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class GameResultTraitSummaryService
{
    use ErDevTrait;
    protected RankRangeService $rankRangeService;
    protected GameResultService $gameResultService;
    protected VersionedGameTableManager $versionedTableManager;
    protected TraitGroupService $traitGroupService;

    public function __construct()
    {
        $this->rankRangeService = new RankRangeService();
        $this->gameResultService = new GameResultService();
        $this->versionedTableManager = new VersionedGameTableManager();
        $this->traitGroupService = new TraitGroupService();
    }

    protected function getVersionedTableName(array $filters): string
    {
        $versionSeason = $filters['version_season'] ?? null;
        $versionMajor = $filters['version_major'] ?? null;
        $versionMinor = $filters['version_minor'] ?? null;
        $versionHotfix = $filters['version_hotfix'] ?? null;

        // 버전이 아예 지정되지 않은 경우에만 최신 버전으로 채운다.
        // 핫픽스는 null 자체가 "핫픽스 없는 버전"을 뜻하므로 개별 폴백을 하면 안 된다.
        if ($versionSeason === null || $versionMajor === null || $versionMinor === null) {
            $latestVersion = VersionHistory::active()->latest('created_at')->first();
            $versionSeason = $versionSeason ?? $latestVersion->version_season;
            $versionMajor = $versionMajor ?? $latestVersion->version_major;
            $versionMinor = $versionMinor ?? $latestVersion->version_minor;
            $versionHotfix = $versionHotfix ?? $latestVersion->version_hotfix;
        }

        return VersionedGameTableManager::getTableName('game_results_trait_summary', [
            'version_season' => $versionSeason,
            'version_major' => $versionMajor,
            'version_minor' => $versionMinor,
            'version_hotfix' => $versionHotfix,
        ]);
    }


    /**
     * 게임 결과 데이터 삽입
     * @return void
     */
    public function updateGameResultTraitSummary($versionSeason = null, $versionMajor = null, $versionMinor = null, $versionHotfix = null)
    {
        DB::disableQueryLog(); // 쿼리 로그 비활성화
        Log::channel('updateGameResultTraitSummary')->info('S: game result trait summary');

        // 버전 인자가 하나도 없으면 최신 버전(핫픽스 포함)을 대상으로 집계한다.
        $versionGiven = $versionSeason !== null || $versionMajor !== null || $versionMinor !== null;
        $latestVersion = VersionHistory::active()->latest('created_at')->first();
        $versionSeason = $versionSeason ?? $latestVersion->version_season;
        $versionMajor = $versionMajor ?? $latestVersion->version_major;
        $versionMinor = $versionMinor ?? $latestVersion->version_minor;
        if (!$versionGiven) {
            $versionHotfix = $versionHotfix ?? $latestVersion->version_hotfix;
        }

        // 버전별 테이블명 생성
        $versionFilters = [
            'version_season' => $versionSeason,
            'version_major' => $versionMajor,
            'version_minor' => $versionMinor,
            'version_hotfix' => $versionHotfix,
        ];
        $tableName = VersionedGameTableManager::getTableName('game_results_trait_summary', $versionFilters);

        Log::channel('updateGameResultTraitSummary')->info("Using versioned table: {$tableName}");

        // 테이블 존재 확인 및 생성
        $this->versionedTableManager->ensureGameResultTraitSummaryTableExists($tableName);

        $tiers = $this->tierRange;

        // TRUNCATE는 DDL이므로 트랜잭션 밖에서 실행 (암묵적 커밋 방지)
        Log::channel('updateGameResultTraitSummary')->info('Truncating table...');
        DB::table($tableName)->truncate();
        Log::channel('updateGameResultTraitSummary')->info("Truncated table {$tableName}");

        // 데이터 처리하면서 바로 insert
        $insertChunkSize = 500;
        $totalInserted = 0;
        $batchData = [];

        try {
            foreach ($tiers as $tier) {
                echo "game result trait S : {$tier['tier']} {$tier['tierNumber']} \n";
                $minScore = $this->rankRangeService->getMinScore($tier['tier'], $tier['tierNumber'], $versionFilters) ?: 0;
                $minTier = $tier['tier'].$tier['tierNumber'];
                $gameResultsCursor = $this->gameResultService->getGameResultByTrait([
                    'version_season' => $versionSeason,
                    'version_major' => $versionMajor,
                    'version_minor' => $versionMinor,
                    'version_hotfix' => $versionHotfix,
                    'min_tier' => $minTier,
                    'min_score' => $minScore,
                ]);

                foreach ($gameResultsCursor as $gameResult) {
                    $batchData[] = [
                        'character_id' => $gameResult->character_id,
                        'trait_id' => $gameResult->trait_id,
                        'is_main' => $gameResult->is_main,
                        'weapon_type' => $gameResult->weapon_type,
                        'game_rank' => $gameResult->game_rank,
                        'game_rank_count' => $gameResult->game_rank_count,
                        'avg_mmr_gain' => $gameResult->avg_mmr_gain,
                        'avg_team_kill_score' => $gameResult->avg_team_kill_score ?? null,
                        'positive_count' => $gameResult->positive_count ?? 0,
                        'negative_count' => $gameResult->negative_count ?? 0,
                        'positive_avg_mmr_gain' => $gameResult->positive_avg_mmr_gain ?? 0,
                        'negative_avg_mmr_gain' => $gameResult->negative_avg_mmr_gain ?? 0,
                        'min_tier' => $minTier,
                        'min_score' => $minScore,
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
                unset($gameResultsCursor);
                gc_collect_cycles();
            }

            // 남은 데이터 insert
            if (!empty($batchData)) {
                DB::table($tableName)->insert($batchData);
                $totalInserted += count($batchData);
            }

            Log::channel('updateGameResultTraitSummary')->info("Inserted {$totalInserted} new records");
            Log::channel('updateGameResultTraitSummary')->info('E: game result trait summary');
        } catch (\Exception $e) {
            Log::channel('updateGameResultTraitSummary')->error('trait Error: ' . $e->getMessage());
            Log::channel('updateGameResultTraitSummary')->error($e->getTraceAsString());
            throw $e;
        } finally {
            // 메모리 정리
            gc_collect_cycles();
        }
    }

    public function getDetail(array $filters)
    {
        $tableName = $this->getVersionedTableName($filters);

        // 테이블 존재 여부 확인
        if (!Schema::hasTable($tableName)) {
            return [
                'data' => [],
                'total' => [],
                'aggregatedData' => []
            ];
        }

        // 특성 그룹은 패치마다 바뀌므로 해당 버전 기준으로 조회한다.
        $groupMap = $this->traitGroupService->getGroupMap([
            'version_season' => $filters['version_season'] ?? null,
            'version_major' => $filters['version_major'] ?? null,
            'version_minor' => $filters['version_minor'] ?? null,
        ]);

        unset($filters['version_season'], $filters['version_major'], $filters['version_minor'], $filters['version_hotfix']);

        $filters['weapon_type'] = $this->replaceWeaponType($filters['weapon_type'], 'en');
        if (isset($filters['character_name'])) {
            $filters['c.name'] = $filters['character_name'];
            unset($filters['character_name']);
        }

        // 캐시 키 생성
        $cacheKey = "trait_summary_" . md5($tableName . json_encode($filters));
        $cacheDuration = 60 * 10; // 10분 캐싱

        $data = cache()->remember($cacheKey, $cacheDuration, function () use ($tableName, $filters) {
            return DB::table($tableName . ' as gts')
                ->select(
                    'gts.*',
                    'c.name as character_name',
                    't.name as trait_name',
                    't.id as trait_id',
                    't.category as trait_category',
                    't.tooltip as trait_tooltip'
                )
                ->join('traits as t', 't.id', '=', 'gts.trait_id')
                ->join('characters as c', 'c.id', '=', 'gts.character_id')
                ->where($filters)
                ->orderBy('game_rank_count', 'desc')
                ->orderBy('game_rank', 'asc')
                ->get();
        });
        $total = array();
        $result = array();
        foreach ($data as $item) {
            if (!isset($total[$item->trait_id])) {
                $total[$item->trait_id] = 0;
            }
            $total[$item->trait_id] += $item->game_rank_count;
            $item->positive_count_percent = $item->game_rank_count ? $item->positive_count / $item->game_rank_count * 100 : 0;
            $item->negative_count_percent = $item->game_rank_count ? $item->negative_count / $item->game_rank_count * 100 : 0;
        }
        foreach ($data as $item) {
            $item->game_rank_count_percent = $total[$item->trait_id] ? $item->game_rank_count / $total[$item->trait_id] * 100 : 0;
            $item->weapon_type = $this->replaceWeaponType($item->weapon_type, 'ko');
            if (!isset($result[$item->trait_id])) {
                $result[$item->trait_id] = array();
                foreach(range(1, 4) as $rank) {
                    $result[$item->trait_id][$rank] = (object) [
                        "character_name" => $item->character_name,
                        "trait_id" => $item->trait_id,
                        "trait_category" => $item->trait_category,
                        "trait_name" => $item->trait_name,
                        "trait_tooltip" => $item->trait_tooltip,
                        "is_main" => $item->is_main,
                        "trait_group" => $groupMap[$item->trait_id] ?? ($item->is_main ? 'main' : null),
                        "character_id" => $item->character_id,
                        "weapon_type" => $item->weapon_type,
                        "game_rank" => $rank,
                        "game_rank_count" => 0,
                        "positive_count" => 0,
                        "negative_count" => 0,
                        "avg_mmr_gain" => 0,
                        "positive_avg_mmr_gain" => 0,
                        "negative_avg_mmr_gain" => 0,
                        "min_tier" => $item->min_tier,
                        "min_score" => $item->min_score,
                        "created_at" => "0000-00-00 00:00:00",
                        "updated_at" => "0000-00-00 00:00:00",
                        "positive_count_percent" => 0,
                        "negative_count_percent" => 0,
                        "game_rank_count_percent" => 0,
                    ];
                }
            }
            // 위에서 만든 초기 객체를 원본 행으로 덮어쓰므로, 원본 행에도 그룹을 붙여준다.
            // (붙이지 않으면 뒤의 집계에서 trait_group 이 사라져 서브가 전부 폴백값으로 표시된다)
            $item->trait_group = $groupMap[$item->trait_id] ?? ($item->is_main ? 'main' : null);
            $result[$item->trait_id][$item->game_rank] = $item;
        }

        // Sort by total usage count
        uksort($result, function($idA, $idB) use ($total) {
            $totalA = isset($total[$idA]) ? $total[$idA] : 0;
            $totalB = isset($total[$idB]) ? $total[$idB] : 0;
            return $totalB - $totalA;
        });

        // 특성별 집계 데이터 생성 (특성 조합 통계와 동일한 형식)
        $aggregatedData = [];
        foreach ($result as $traitId => $rankData) {
            $firstRank = $rankData[1] ?? $rankData[2] ?? $rankData[3] ?? $rankData[4] ?? null;
            if (!$firstRank) continue;

            $gameCount = 0;
            $top1Count = 0;
            $top2Count = 0;
            $top4Count = 0;
            $positiveCount = 0;
            $negativeCount = 0;
            $totalMmrGain = 0;
            $totalPositiveMmrGain = 0;
            $totalNegativeMmrGain = 0;
            $totalTeamKillScore = 0;
            $positiveGames = 0;
            $negativeGames = 0;

            foreach ($rankData as $rank => $item) {
                $gameCount += $item->game_rank_count;
                if ($rank == 1) $top1Count = $item->game_rank_count;
                if ($rank <= 2) $top2Count += $item->game_rank_count;
                if ($rank <= 4) $top4Count += $item->game_rank_count;
                $positiveCount += $item->positive_count;
                $negativeCount += $item->negative_count;
                $totalMmrGain += $item->avg_mmr_gain * $item->game_rank_count;
                $totalTeamKillScore += ($item->avg_team_kill_score ?? 0) * $item->game_rank_count;
                // 이득/손실 평균 점수 계산: positive_count가 있으면 해당 평균 점수 누적
                if ($item->positive_count > 0) {
                    $totalPositiveMmrGain += ($item->positive_avg_mmr_gain ?? 0) * $item->positive_count;
                    $positiveGames += $item->positive_count;
                }
                if ($item->negative_count > 0) {
                    $totalNegativeMmrGain += ($item->negative_avg_mmr_gain ?? 0) * $item->negative_count;
                    $negativeGames += $item->negative_count;
                }
            }

            $aggregatedData[] = [
                'trait_id' => $traitId,
                'trait_name' => $firstRank->trait_name,
                'trait_category' => $firstRank->trait_category,
                'trait_tooltip' => $firstRank->trait_tooltip ?? '',
                'is_main' => $firstRank->is_main,
                'trait_group' => $firstRank->trait_group ?? ($firstRank->is_main ? 'main' : null),
                'game_count' => $gameCount,
                'top1_count' => $top1Count,
                'top2_count' => $top2Count,
                'top4_count' => $top4Count,
                'top1_count_percent' => $gameCount > 0 ? round($top1Count / $gameCount * 100, 2) : 0,
                'top2_count_percent' => $gameCount > 0 ? round($top2Count / $gameCount * 100, 2) : 0,
                'top4_count_percent' => $gameCount > 0 ? round($top4Count / $gameCount * 100, 2) : 0,
                'avg_mmr_gain' => $gameCount > 0 ? round($totalMmrGain / $gameCount, 1) : 0,
                'avg_team_kill_score' => $gameCount > 0 ? round($totalTeamKillScore / $gameCount, 2) : 0,
                'positive_game_count' => $positiveCount,
                'negative_game_count' => $negativeCount,
                'positive_game_count_percent' => $gameCount > 0 ? round($positiveCount / $gameCount * 100, 2) : 0,
                'negative_game_count_percent' => $gameCount > 0 ? round($negativeCount / $gameCount * 100, 2) : 0,
                'positive_avg_mmr_gain' => $positiveGames > 0 ? round($totalPositiveMmrGain / $positiveGames, 1) : 0,
                'negative_avg_mmr_gain' => $negativeGames > 0 ? round($totalNegativeMmrGain / $negativeGames, 1) : 0,
                'endgame_win_percent' => $top2Count > 0 ? round($top1Count / $top2Count * 100, 2) : 0,
            ];
        }

        // 사용수 기준 정렬
        usort($aggregatedData, function($a, $b) {
            return $b['game_count'] - $a['game_count'];
        });

        return [
            'data' => $result,
            'total' => $total,
            'aggregatedData' => $aggregatedData
        ];
    }

    private function setEmptyObject()
    {
        return new GameResultTraitSummary();
    }
}
