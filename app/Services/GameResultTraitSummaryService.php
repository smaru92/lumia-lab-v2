<?php

namespace App\Services;

use App\Models\GameResultTraitSummary;
use App\Models\VersionHistory;
use App\Traits\ErDevTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GameResultTraitSummaryService
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
    public function updateGameResultTraitSummary($versionSeason = null, $versionMajor = null, $versionMinor = null)
    {
        DB::disableQueryLog(); // 쿼리 로그 비활성화
        Log::channel('updateGameResultTraitSummary')->info('S: game result tactical_skill summary');

        $latestVersion = VersionHistory::latest('created_at')->first();
        $versionSeason = $versionSeason ?? $latestVersion->version_season;
        $versionMajor = $versionMajor ?? $latestVersion->version_major;
        $versionMinor = $versionMinor ?? $latestVersion->version_minor;
        $tiers = $this->tierRange;

        $allInsertData = []; // 모든 insert 데이터를 임시로 모음

        // 트랜잭션 밖에서 데이터 수집 (기존 데이터는 그대로 유지)
        try {
            foreach ($tiers as $tier) {
                echo "game result trait S : {$tier['tier']} {$tier['tierNumber']} \n";
                $versionFilters = [
                    'version_season' => $versionSeason,
                    'version_major' => $versionMajor,
                    'version_minor' => $versionMinor
                ];
                $minScore = $this->rankRangeService->getMinScore($tier['tier'], $tier['tierNumber'], $versionFilters) ?: 0;
                $minTier = $tier['tier'].$tier['tierNumber'];
                $gameResultsCursor = $this->gameResultService->getGameResultByTrait([
                    'version_season' => $versionSeason,
                    'version_major' => $versionMajor,
                    'version_minor' => $versionMinor,
                    'min_tier' => $minTier,
                    'min_score' => $minScore,
                ]);

                foreach ($gameResultsCursor as $gameResult) {
                    $allInsertData[] = [
                        'character_id' => $gameResult->character_id,
                        'trait_id' => $gameResult->trait_id,
                        'is_main' => $gameResult->is_main,
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
                }

                // 티어별 처리 후 메모리 정리
                unset($gameResultsCursor);
                gc_collect_cycles();
            }

            // 트랜잭션 시작: 빠르게 삭제 후 insert
            DB::beginTransaction();

            // 기존 데이터 삭제
            GameResultTraitSummary::where('version_season', $versionSeason)
                ->where('version_major', $versionMajor)
                ->where('version_minor', $versionMinor)
                ->delete();

            // 새 데이터를 chunk로 insert
            $chunkSize = 100;
            foreach (array_chunk($allInsertData, $chunkSize) as $chunk) {
                GameResultTraitSummary::insert($chunk);
            }

            DB::commit();
            Log::channel('updateGameResultTraitSummary')->info('E: game result tactical_skill summary');
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::channel('updateGameResultTraitSummary')->error('rank Error: ' . $e->getMessage());
            Log::channel('updateGameResultTraitSummary')->error($e->getTraceAsString());
            throw $e;
        } finally {
            // 메모리 정리
            unset($allInsertData);
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

        // 캐시 키 생성
        $cacheKey = "trait_summary_" . md5(json_encode($filters));
        $cacheDuration = 60 * 10; // 10분 캐싱

        $data = cache()->remember($cacheKey, $cacheDuration, function () use ($filters) {
            return GameResultTraitSummary::select(
                'game_results_trait_summary.*',
                'c.name as character_name',
                't.name as trait_name',
                't.id as trait_id',
                't.category as trait_category'
            )
                ->join('traits as t', 't.id', 'game_results_trait_summary.trait_id')
                ->join('characters as c', 'c.id', 'game_results_trait_summary.character_id')
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
                        "is_main" => $item->is_main,
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
            $result[$item->trait_id][$item->game_rank] = $item;
        }

        // Sort by total usage count
        uksort($result, function($idA, $idB) use ($total) {
            $totalA = isset($total[$idA]) ? $total[$idA] : 0;
            $totalB = isset($total[$idB]) ? $total[$idB] : 0;
            return $totalB - $totalA;
        });

        return [
            'data' => $result,
            'total' => $total
        ];
    }

    private function setEmptyObject()
    {
        return new GameResultTraitSummary();
    }
}
