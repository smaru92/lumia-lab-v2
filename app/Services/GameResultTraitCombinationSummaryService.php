<?php

namespace App\Services;

use App\Models\GameResultTraitCombinationSummary;
use App\Models\VersionHistory;
use App\Traits\ErDevTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GameResultTraitCombinationSummaryService
{
    use ErDevTrait;

    protected RankRangeService $rankRangeService;
    protected GameResultService $gameResultService;
    protected string $logChannel = 'updateGameResultTraitCombinationSummary';

    public function __construct()
    {
        $this->rankRangeService = new RankRangeService();
        $this->gameResultService = new GameResultService();
    }

    /**
     * 특성 조합별 통계 데이터 갱신
     */
    public function updateGameResultTraitCombinationSummary($versionSeason = null, $versionMajor = null, $versionMinor = null)
    {
        DB::disableQueryLog();
        Log::channel($this->logChannel)->info('S: game result trait combination summary');

        $latestVersion = VersionHistory::latest('created_at')->first();
        $versionSeason = $versionSeason ?? $latestVersion->version_season;
        $versionMajor = $versionMajor ?? $latestVersion->version_major;
        $versionMinor = $versionMinor ?? $latestVersion->version_minor;
        $tiers = $this->tierRange;

        // 트랜잭션으로 delete와 insert를 묶어서 처리
        DB::beginTransaction();

        try {
            // 1단계: 기존 데이터 삭제 (청크 단위)
            $deleteChunkSize = 5000;
            $deletedCount = 0;

            Log::channel($this->logChannel)->info('Deleting old records...');
            do {
                $deleted = GameResultTraitCombinationSummary::where('version_season', $versionSeason)
                    ->where('version_major', $versionMajor)
                    ->where('version_minor', $versionMinor)
                    ->limit($deleteChunkSize)
                    ->delete();

                $deletedCount += $deleted;

                if ($deletedCount % 20000 === 0) {
                    gc_collect_cycles();
                }
            } while ($deleted > 0);

            Log::channel($this->logChannel)->info("Deleted {$deletedCount} old records");

            // 2단계: 티어별 데이터 처리
            $insertChunkSize = 500;
            $totalInserted = 0;
            $batchData = [];

            foreach ($tiers as $tier) {
                echo "game result trait combination S : {$tier['tier']} {$tier['tierNumber']} \n";

                $versionFilters = [
                    'version_season' => $versionSeason,
                    'version_major' => $versionMajor,
                    'version_minor' => $versionMinor
                ];
                $minScore = $this->rankRangeService->getMinScore($tier['tier'], $tier['tierNumber'], $versionFilters) ?: 0;
                $minTier = $tier['tier'] . $tier['tierNumber'];

                $gameResults = $this->gameResultService->getGameResultByTraitCombination([
                    'version_season' => $versionSeason,
                    'version_major' => $versionMajor,
                    'version_minor' => $versionMinor,
                    'min_tier' => $minTier,
                    'min_score' => $minScore,
                ]);

                foreach ($gameResults['data'] as $item) {
                    $batchData[] = [
                        'character_id' => $item['characterId'],
                        'character_name' => $item['characterName'],
                        'weapon_type' => $item['weaponType'],
                        'trait_ids' => $item['traitIds'],
                        'min_tier' => $minTier,
                        'min_score' => $minScore,
                        'game_count' => $item['gameCount'],
                        'positive_game_count' => $item['positiveGameCount'],
                        'negative_game_count' => $item['negativeGameCount'],
                        'game_count_percent' => $item['gameCountPercent'],
                        'positive_game_count_percent' => $item['positiveGameCountPercent'],
                        'negative_game_count_percent' => $item['negativeGameCountPercent'],
                        'top1_count' => $item['top1Count'],
                        'top2_count' => $item['top2Count'],
                        'top4_count' => $item['top4Count'],
                        'top1_count_percent' => $item['top1CountPercent'],
                        'top2_count_percent' => $item['top2CountPercent'],
                        'top4_count_percent' => $item['top4CountPercent'],
                        'endgame_win_percent' => $item['endgameWinPercent'],
                        'avg_mmr_gain' => $item['avgMmrGain'],
                        'positive_avg_mmr_gain' => $item['positiveAvgMmrGain'],
                        'negative_avg_mmr_gain' => $item['negativeAvgMmrGain'],
                        'avg_team_kill_score' => $item['avgTeamKillScore'],
                        'version_season' => $versionSeason,
                        'version_major' => $versionMajor,
                        'version_minor' => $versionMinor,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ];

                    if (count($batchData) >= $insertChunkSize) {
                        GameResultTraitCombinationSummary::insert($batchData);
                        $totalInserted += count($batchData);
                        $batchData = [];

                        if ($totalInserted % 5000 === 0) {
                            gc_collect_cycles();
                        }
                    }
                }

                unset($gameResults);
                gc_collect_cycles();
            }

            // 남은 데이터 insert
            if (!empty($batchData)) {
                GameResultTraitCombinationSummary::insert($batchData);
                $totalInserted += count($batchData);
            }

            DB::commit();

            Log::channel($this->logChannel)->info("Inserted {$totalInserted} new records");
            Log::channel($this->logChannel)->info('E: game result trait combination summary');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel($this->logChannel)->error('Error: ' . $e->getMessage());
            Log::channel($this->logChannel)->error($e->getTraceAsString());
            throw $e;
        } finally {
            gc_collect_cycles();
        }
    }

    /**
     * 캐릭터별 특성 조합 통계 조회
     */
    public function getDetail(array $filters)
    {
        // weapon_type 영어로 변환
        if (isset($filters['weapon_type'])) {
            $filters['weapon_type'] = $this->replaceWeaponType($filters['weapon_type'], 'en');
        }

        $query = GameResultTraitCombinationSummary::select(
            'game_results_trait_combination_summary.*'
        );

        if (isset($filters['version_season'])) {
            $query->where('version_season', $filters['version_season']);
        }
        if (isset($filters['version_major'])) {
            $query->where('version_major', $filters['version_major']);
        }
        if (isset($filters['version_minor'])) {
            $query->where('version_minor', $filters['version_minor']);
        }
        if (isset($filters['min_tier'])) {
            $query->where('min_tier', $filters['min_tier']);
        }
        if (isset($filters['character_name'])) {
            $query->where('character_name', $filters['character_name']);
        }
        if (isset($filters['weapon_type']) && $filters['weapon_type'] !== 'All') {
            $query->where('weapon_type', $filters['weapon_type']);
        }

        $data = $query->orderBy('game_count', 'desc')->get();

        $total = 0;
        foreach ($data as $item) {
            $total += $item->game_count;
        }

        return [
            'data' => $data,
            'total' => $total,
        ];
    }
}