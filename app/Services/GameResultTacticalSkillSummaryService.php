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

        // ✅ **트랜잭션 시작**
        DB::beginTransaction();
        try {
            // ✅ **기존 데이터 삭제**
            GameResultTacticalSkillSummary::where('version_season', $versionSeason)
                ->where('version_major', $versionMajor)
                ->where('version_minor', $versionMinor)
                ->delete();

            // Removed the large $bulkInsertData array initialization

            foreach ($tiers as $tier) {
                $versionFilters = [
                    'version_season' => $versionSeason,
                    'version_major' => $versionMajor,
                    'version_minor' => $versionMinor
                ];
                $minScore = $this->rankRangeService->getMinScore($tier['tier'], $tier['tierNumber'], $versionFilters) ?: 0;
                $minTier = $tier['tier'].$tier['tierNumber'];
                $gameResultsCursor = $this->gameResultService->getGameResultByTacticalSkill([ // Renamed to indicate it's a cursor
                    'version_season' => $versionSeason,
                    'version_major' => $versionMajor,
                    'version_minor' => $versionMinor,
                    'min_tier' => $minTier,
                    'min_score' => $minScore,
                ]);

                $chunkData = []; // Initialize chunk array inside the tier loop
                $chunkSize = 100; // Define chunk size

                foreach ($gameResultsCursor as $gameResult) { // Iterate through the cursor
                    $chunkData[] = [ // Add data to the current chunk
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

                    // Insert chunk when it reaches the defined size
                    if (count($chunkData) >= $chunkSize) {
                        GameResultTacticalSkillSummary::insert($chunkData);
                        $chunkData = []; // Reset chunk array
                    }
                }

                // Insert any remaining data in the last chunk
                if (!empty($chunkData)) {
                    GameResultTacticalSkillSummary::insert($chunkData);
                }
            }

            // Removed the final bulk insert logic as it's now handled within the loop

            // ✅ **트랜잭션 커밋**
            DB::commit();
            Log::channel('updateGameResultTacticalSkillSummary')->info('E: game result tactical_skill summary');
        } catch (\Exception $e) {
            // ❌ **오류 발생 시 롤백**
            DB::rollBack();
            Log::channel('updateGameResultTacticalSkillSummary')->error('rank Error: ' . $e->getMessage());
            Log::channel('updateGameResultTacticalSkillSummary')->error($e->getTraceAsString()); // 💡 스택트레이스 추가
            throw $e;
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

        return [
            'data' => $result,
            'total' => $total
        ];
    }

    private function setEmptyObject()
    {
        return new GameResultTacticalSkillSummary();
    }
}
