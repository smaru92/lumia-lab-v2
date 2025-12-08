<?php

namespace App\Services;

use App\Models\GameResultTacticalSkillSummary;
use App\Models\VersionHistory;
use App\Traits\ErDevTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GameResultTacticalSkillSummaryService
{
    use ErDevTrait;
    protected RankRangeService $rankRangeService;
    protected GameResultService $gameResultService;
    public function __construct()
    {
        $this->rankRangeService = new RankRangeService();
        $this->gameResultService = new GameResultService();
    }


    /**
     * 게임 결과 데이터 삽입
     * @return void
     */
    public function updateGameResultTacticalSkillSummary($versionSeason = null, $versionMajor = null, $versionMinor = null)
    {
        DB::disableQueryLog(); // 쿼리 로그 비활성화
        Log::channel('updateGameResultTacticalSkillSummary')->info('S: game result tactical_skill summary');

        $latestVersion = VersionHistory::latest('created_at')->first();
        $versionSeason = $versionSeason ?? $latestVersion->version_season;
        $versionMajor = $versionMajor ?? $latestVersion->version_major;
        $versionMinor = $versionMinor ?? $latestVersion->version_minor;
        $tiers = $this->tierRange;

        try {
            // 1단계: 기존 데이터 삭제 (청크 단위)
            $deleteChunkSize = 5000;
            $deletedCount = 0;

            Log::channel('updateGameResultTacticalSkillSummary')->info('Deleting old records...');
            do {
                $deleted = GameResultTacticalSkillSummary::where('version_season', $versionSeason)
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

            Log::channel('updateGameResultTacticalSkillSummary')->info("Deleted {$deletedCount} old records");

            // 2단계: 데이터 처리하면서 바로 insert (메모리에 모두 쌓지 않음)
            $insertChunkSize = 500;
            $totalInserted = 0;
            $batchData = [];

            foreach ($tiers as $tier) {
                $versionFilters = [
                    'version_season' => $versionSeason,
                    'version_major' => $versionMajor,
                    'version_minor' => $versionMinor
                ];
                $minScore = $this->rankRangeService->getMinScore($tier['tier'], $tier['tierNumber'], $versionFilters) ?: 0;
                $minTier = $tier['tier'].$tier['tierNumber'];
                $gameResultsCursor = $this->gameResultService->getGameResultByTacticalSkill([
                    'version_season' => $versionSeason,
                    'version_major' => $versionMajor,
                    'version_minor' => $versionMinor,
                    'min_tier' => $minTier,
                    'min_score' => $minScore,
                ]);

                foreach ($gameResultsCursor as $gameResult) {
                    $batchData[] = [
                        'character_id' => $gameResult->character_id,
                        'tactical_skill_id' => $gameResult->tactical_skill_id,
                        'tactical_skill_level' => $gameResult->tactical_skill_level,
                        'weapon_type' => $gameResult->weapon_type,
                        'game_rank' => $gameResult->game_rank,
                        'game_rank_count' => $gameResult->game_rank_count,
                        'avg_mmr_gain' => $gameResult->avg_mmr_gain,
                        'avg_team_kill_score' => $gameResult->avg_team_kill_score ?? null,
                        'positive_count' => $gameResult->positive_count,
                        'negative_count' => $gameResult->negative_count,
                        'positive_avg_mmr_gain' => $gameResult->positive_avg_mmr_gain,
                        'negative_avg_mmr_gain' => $gameResult->negative_avg_mmr_gain,
                        'min_tier' => $minTier,
                        'min_score' => $minScore,
                        'version_season' => $versionSeason,
                        'version_major' => $versionMajor,
                        'version_minor' => $versionMinor,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ];

                    // 일정 크기마다 insert
                    if (count($batchData) >= $insertChunkSize) {
                        GameResultTacticalSkillSummary::insert($batchData);
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
                GameResultTacticalSkillSummary::insert($batchData);
                $totalInserted += count($batchData);
            }

            Log::channel('updateGameResultTacticalSkillSummary')->info("Inserted {$totalInserted} new records");
            Log::channel('updateGameResultTacticalSkillSummary')->info('E: game result tactical_skill summary');
        } catch (\Exception $e) {
            Log::channel('updateGameResultTacticalSkillSummary')->error('rank Error: ' . $e->getMessage());
            Log::channel('updateGameResultTacticalSkillSummary')->error($e->getTraceAsString());
            throw $e;
        } finally {
            // 메모리 정리
            gc_collect_cycles();
        }
    }

    public function getDetail(array $filters)
    {
        $filters['weapon_type'] = $this->replaceWeaponType($filters['weapon_type'], 'en');
        if (isset($filters['character_name'])) {
            $filters['c.name'] = $filters['character_name'];
            unset($filters['character_name']);
        }
        $data = GameResultTacticalSkillSummary::select(
            'game_results_tactical_skill_summary.*',
            'c.name as character_name',
            'ts.name as tactical_skill_name',
            'ts.id as tactical_skill_id',
            'ts.tooltip as tactical_skill_tooltip',
        )
            ->join('tactical_skills as ts', 'ts.id', 'game_results_tactical_skill_summary.tactical_skill_id')
            ->join('characters as c', 'c.id', 'game_results_tactical_skill_summary.character_id')
            ->where($filters)
            ->orderBy('game_rank_count', 'desc')
            ->orderBy('game_rank', 'asc')
            ->get();
        $total = array();
        $result = array();
        foreach ($data as $item) {
            if (!isset($total[$item->tactical_skill_id])) {
                $total[$item->tactical_skill_id] = array(
                    1 => 0,
                    2 => 0,
                );
            }
            if (!isset($total[$item->tactical_skill_id][$item->tactical_skill_level])) {
                $total[$item->tactical_skill_id][$item->tactical_skill_level] = 0;
            }
            $total[$item->tactical_skill_id][$item->tactical_skill_level] += $item->game_rank_count;
            $item->positive_count_percent = $item->game_rank_count ? $item->positive_count / $item->game_rank_count * 100 : 0;
            $item->negative_count_percent = $item->game_rank_count ? $item->negative_count / $item->game_rank_count * 100 : 0;
        }
        foreach ($data as $item) {
            $item->game_rank_count_percent = $total[$item->tactical_skill_id][$item->tactical_skill_level] ? $item->game_rank_count / $total[$item->tactical_skill_id][$item->tactical_skill_level] * 100 : 0;
            $item->weapon_type = $this->replaceWeaponType($item->weapon_type, 'ko');
            if (!isset($result[$item->tactical_skill_id])) {
                $result[$item->tactical_skill_id] = array(
                    '1' => array(),
                    '2' => array(),
                );
                foreach(range(1, 4) as $rank) {
                    $result[$item->tactical_skill_id][1][$rank] = (object) [
                        "character_name" => $item->character_name,
                        "tactical_skill_id" => $item->tactical_skill_id,
                        "tactical_skill_name" => $item->tactical_skill_name,
                        "tactical_skill_tooltip" => $item->tactical_skill_tooltip,
                        "tactical_skill_level" => 1,
                        "equipment_id" => $item->equipment_id,
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
                        "version_major" => $item->version_major,
                        "version_minor" => $item->version_minor,
                        "created_at" => "0000-00-00 00:00:00",
                        "updated_at" => "0000-00-00 00:00:00",
                        "positive_count_percent" => 0,
                        "negative_count_percent" => 0,
                        "game_rank_count_percent" => 0,
                    ];
                    $result[$item->tactical_skill_id][2][$rank] = (object) [
                        "character_name" => $item->character_name,
                        "tactical_skill_id" => $item->tactical_skill_id,
                        "tactical_skill_name" => $item->tactical_skill_name,
                        "tactical_skill_tooltip" => $item->tactical_skill_tooltip,
                        "tactical_skill_level" => 2,
                        "equipment_id" => $item->equipment_id,
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
                        "version_major" => $item->version_major,
                        "version_minor" => $item->version_minor,
                        "created_at" => "0000-00-00 00:00:00",
                        "updated_at" => "0000-00-00 00:00:00",
                        "positive_count_percent" => 0,
                        "negative_count_percent" => 0,
                        "game_rank_count_percent" => 0,
                    ];
                }
            }
            $result[$item->tactical_skill_id][$item->tactical_skill_level][$item->game_rank] = $item;
        }

        // Sort by total usage count (sum of all levels for each tactical skill)
        uksort($result, function($idA, $idB) use ($total) {
            $totalA = isset($total[$idA]) ? array_sum($total[$idA]) : 0;
            $totalB = isset($total[$idB]) ? array_sum($total[$idB]) : 0;
            return $totalB - $totalA;
        });

        // 전술스킬별 집계 데이터 생성 (특성 조합 통계와 동일한 형식)
        $aggregatedData = [];
        foreach ($result as $skillId => $levelData) {
            foreach ($levelData as $level => $rankData) {
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
                    'tactical_skill_id' => $skillId,
                    'tactical_skill_name' => $firstRank->tactical_skill_name,
                    'tactical_skill_tooltip' => $firstRank->tactical_skill_tooltip ?? '',
                    'tactical_skill_level' => $level,
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
        }

        // 전술스킬별 총 사용수 계산 (Lv1 + Lv2 합계)
        $skillTotals = [];
        foreach ($aggregatedData as $item) {
            $skillId = $item['tactical_skill_id'];
            if (!isset($skillTotals[$skillId])) {
                $skillTotals[$skillId] = 0;
            }
            $skillTotals[$skillId] += $item['game_count'];
        }

        // 전술스킬ID 기준 그룹화 후 정렬 (같은 스킬끼리 묶고, 총 사용수 기준 정렬, 그 안에서 레벨 순)
        usort($aggregatedData, function($a, $b) use ($skillTotals) {
            // 1차: 전술스킬 총 사용수 기준 내림차순
            $totalA = $skillTotals[$a['tactical_skill_id']] ?? 0;
            $totalB = $skillTotals[$b['tactical_skill_id']] ?? 0;
            if ($totalA !== $totalB) {
                return $totalB - $totalA;
            }
            // 2차: 같은 스킬이면 레벨 순 오름차순
            if ($a['tactical_skill_id'] === $b['tactical_skill_id']) {
                return $a['tactical_skill_level'] - $b['tactical_skill_level'];
            }
            // 3차: 스킬ID 순
            return $a['tactical_skill_id'] - $b['tactical_skill_id'];
        });

        return [
            'data' => $result,
            'total' => $total,
            'aggregatedData' => $aggregatedData
        ];
    }

    private function setEmptyObject()
    {
        return new GameResultTacticalSkillSummary();
    }
}
