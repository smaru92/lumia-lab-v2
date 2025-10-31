<?php

namespace App\Services;

use App\Models\GameResultEquipmentMainSummary;
use App\Models\VersionHistory;
use App\Traits\ErDevTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GameResultEquipmentMainSummaryService
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
    public function updateGameResultEquipmentMainSummary($versionSeason = null, $versionMajor = null, $versionMinor = null)
    {
        Log::channel('updateGameResultEquipmentMainSummary')->info('S: game equipment main result summary');

        $latestVersion = VersionHistory::latest('created_at')->first();
        $versionSeason = $versionSeason ?? $latestVersion->version_season;
        $versionMajor = $versionMajor ?? $latestVersion->version_major;
        $versionMinor = $versionMinor ?? $latestVersion->version_minor;

        $tiers = $this->tierRange;

        // ✅ **트랜잭션 시작**
        DB::beginTransaction();
        try {
            // ✅ **기존 데이터 삭제**
            GameResultEquipmentMainSummary::where('version_season', $versionSeason)
                ->where('version_major', $versionMajor)
                ->where('version_minor', $versionMinor)
                ->delete();

            $bulkInsertData = [];

            foreach ($tiers as $tier) {
                $versionFilters = [
                    'version_season' => $versionSeason,
                    'version_major' => $versionMajor,
                    'version_minor' => $versionMinor
                ];
                $minScore = $this->rankRangeService->getMinScore($tier['tier'], $tier['tierNumber'], $versionFilters) ?: 0;
                $minTier = $tier['tier'].$tier['tierNumber'];
                $gameResults = $this->gameResultService->getGameResultEquipmentMain([
                    'version_season' => $versionSeason,
                    'version_major' => $versionMajor,
                    'version_minor' => $versionMinor,
                    'min_tier' => $minTier,
                    'min_score' => $minScore,
                ]);

                $bulkInsertData = []; // Initialize chunk array inside the tier loop
                $chunkSize = 100; // Define chunk size

                $gameResultsCursor = $gameResults['data'];
                foreach ($gameResultsCursor as $gameResult) {
                    $bulkInsertData[] = [
                        'equipment_id' => $gameResult['equipmentId'],
                        'equipment_name' => $gameResult['name'],
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
                        'version_season' => $versionSeason,
                        'version_major' => $versionMajor,
                        'version_minor' => $versionMinor,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ];
                    // Insert chunk when it reaches the defined size
                    if (count($bulkInsertData) >= $chunkSize) {
                        GameResultEquipmentMainSummary::insert($bulkInsertData);
                        $bulkInsertData = []; // Reset chunk array
                    }
                }


                // Insert any remaining data in the last chunk
                if (!empty($bulkInsertData)) {
                    GameResultEquipmentMainSummary::insert($bulkInsertData);
                }
            }

            // ✅ **트랜잭션 커밋**
            DB::commit();
            Log::channel('updateGameResultEquipmentMainSummary')->info('E: game equipment main result summary');
        } catch (\Exception $e) {
            // ❌ **오류 발생 시 롤백**
            DB::rollBack();
            Log::channel('updateGameResultEquipmentMainSummary')->error('Error: ' . $e->getMessage());
            Log::channel('updateGameResultEquipmentMainSummary')->error($e->getTraceAsString()); // 💡 스택트레이스 추가
            throw $e;
        }
    }

    public function getList(array $filters)
    {
        // 장비 페이지용: 랭킹 계산 제거로 성능 최적화
        return GameResultEquipmentMainSummary::select(
            'game_results_equipment_main_summary.*',
            'equipments.item_grade',
            'equipments.item_type2'
        )
            ->join('equipments', 'equipments.id', '=', 'game_results_equipment_main_summary.equipment_id')
            ->where($filters)
            ->whereIn('equipments.item_grade', ['Legend','Mythic'])
            ->orderBy('meta_score', 'desc')
            ->get();
    }

}
