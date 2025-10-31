<?php

namespace App\Services;

use App\Models\GameResultSummary;
use App\Traits\ErDevTrait;
use Illuminate\Support\Facades\DB;

class GameResultSummaryService extends BaseSummaryService
{
    use ErDevTrait;

    public function __construct()
    {
        parent::__construct('updateGameResultSummary');
    }

    public function updateGameResultSummary($versionSeason = null, $versionMajor = null, $versionMinor = null)
    {
        $this->updateSummary($versionSeason, $versionMajor, $versionMinor);
    }

    protected function getSummaryModel(): string
    {
        return GameResultSummary::class;
    }

    protected function getGameResults(array $params): iterable
    {
        // getGameResultMain returns an array ['data' => [...]], so we extract the data part.
        return $this->gameResultService->getGameResultMain($params)['data'] ?? [];
    }

    protected function transformData(object|array $gameResult, string $minTier, int $minScore, int $versionSeason, int $versionMajor, int $versionMinor): array
    {
        // In this specific service, $gameResult is an associative array, not an object.
        return [
            'character_id' => $gameResult['characterId'],
            'character_name' => $gameResult['name'],
            'weapon_type' => $gameResult['weaponType'],
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
    }

    public function getList(array $filters)
    {
        // 메인 페이지용: 랭킹 계산 제거로 성능 최적화 (랭킹은 detail 페이지에서만 사용)
        return GameResultSummary::where($filters)
            ->orderBy('meta_score', 'desc')
            ->get();
    }
    public function getDetail(array $filters)
    {
        $subQueryFilter = $filters;
        unset($subQueryFilter['character_name']);
        unset($subQueryFilter['weapon_type']);
        $subQuery = GameResultSummary::select(
            'game_results_summary.*',
            DB::raw("RANK() OVER (ORDER BY meta_score DESC) AS meta_score_rank"),
            DB::raw("RANK() OVER (ORDER BY game_count DESC) AS game_count_rank"),
            DB::raw("RANK() OVER (ORDER BY top1_count_percent DESC) AS top1_count_percent_rank"),
            DB::raw("RANK() OVER (ORDER BY top2_count_percent DESC) AS top2_count_percent_rank"),
            DB::raw("RANK() OVER (ORDER BY top4_count_percent DESC) AS top4_count_percent_rank"),
            DB::raw("RANK() OVER (ORDER BY endgame_win_percent DESC) AS endgame_win_percent_rank"),
            DB::raw("RANK() OVER (ORDER BY avg_mmr_gain DESC) AS avg_mmr_gain_rank"),
            DB::raw("RANK() OVER (ORDER BY positive_game_count_percent DESC) AS positive_game_count_percent_rank"),
            DB::raw("RANK() OVER (ORDER BY negative_game_count_percent ASC) AS negative_game_count_percent_rank"),
            DB::raw("RANK() OVER (ORDER BY positive_avg_mmr_gain DESC) AS positive_avg_mmr_gain_rank"),
            DB::raw("RANK() OVER (ORDER BY negative_avg_mmr_gain DESC) AS negative_avg_mmr_gain_rank"),
        )
            ->where($subQueryFilter);
        return DB::table(DB::raw("({$subQuery->toSql()}) as ranked"))
            ->mergeBindings($subQuery->getQuery())
            ->where('character_name', $filters['character_name'])
            ->where('weapon_type', $filters['weapon_type'])
            ->first();
    }

    public function getDetailBulk(array $filters, array $tierRange)
    {
        // 모든 티어를 한 번에 조회 (성능 최적화)
        $baseFilters = $filters;
        unset($baseFilters['character_name']);
        unset($baseFilters['weapon_type']);
        unset($baseFilters['min_tier']);

        // PARTITION BY min_tier로 각 티어별 랭킹을 한 번에 계산
        $subQuery = GameResultSummary::select(
            'game_results_summary.*',
            DB::raw("RANK() OVER (PARTITION BY min_tier ORDER BY meta_score DESC) AS meta_score_rank"),
            DB::raw("RANK() OVER (PARTITION BY min_tier ORDER BY game_count DESC) AS game_count_rank"),
            DB::raw("RANK() OVER (PARTITION BY min_tier ORDER BY top1_count_percent DESC) AS top1_count_percent_rank"),
            DB::raw("RANK() OVER (PARTITION BY min_tier ORDER BY top2_count_percent DESC) AS top2_count_percent_rank"),
            DB::raw("RANK() OVER (PARTITION BY min_tier ORDER BY top4_count_percent DESC) AS top4_count_percent_rank"),
            DB::raw("RANK() OVER (PARTITION BY min_tier ORDER BY endgame_win_percent DESC) AS endgame_win_percent_rank"),
            DB::raw("RANK() OVER (PARTITION BY min_tier ORDER BY avg_mmr_gain DESC) AS avg_mmr_gain_rank"),
            DB::raw("RANK() OVER (PARTITION BY min_tier ORDER BY positive_game_count_percent DESC) AS positive_game_count_percent_rank"),
            DB::raw("RANK() OVER (PARTITION BY min_tier ORDER BY negative_game_count_percent ASC) AS negative_game_count_percent_rank"),
            DB::raw("RANK() OVER (PARTITION BY min_tier ORDER BY positive_avg_mmr_gain DESC) AS positive_avg_mmr_gain_rank"),
            DB::raw("RANK() OVER (PARTITION BY min_tier ORDER BY negative_avg_mmr_gain DESC) AS negative_avg_mmr_gain_rank"),
            DB::raw("COUNT(*) OVER (PARTITION BY min_tier) AS rank_count")
        )
            ->where($baseFilters);

        // 필요한 티어들만 필터링
        $tierList = array_map(function($tier) {
            return $tier['tier'] . $tier['tierNumber'];
        }, $tierRange);

        $results = DB::table(DB::raw("({$subQuery->toSql()}) as ranked"))
            ->mergeBindings($subQuery->getQuery())
            ->where('character_name', $filters['character_name'])
            ->where('weapon_type', $filters['weapon_type'])
            ->whereIn('min_tier', $tierList)
            ->get();

        // 티어별로 그룹화 (먼저 임시 배열에 저장)
        $tempResults = [];
        foreach ($results as $result) {
            $tempResults[$result->min_tier] = $result;
        }

        // tierRange 순서대로 정렬된 결과 배열 생성
        $groupedResults = [];
        foreach ($tierRange as $tier) {
            $tierKey = $tier['tier'] . $tier['tierNumber'];
            if (isset($tempResults[$tierKey])) {
                $groupedResults[$tierKey] = $tempResults[$tierKey];
            }
        }

        return $groupedResults;
    }
}

