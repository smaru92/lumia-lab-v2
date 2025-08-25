<?php

namespace App\Services;

use App\Models\GameResultRankSummary;
use App\Traits\ErDevTrait;

class GameResultRankSummaryService extends BaseSummaryService
{
    use ErDevTrait;

    public function __construct()
    {
        parent::__construct('updateGameResultRankSummary');
    }

    public function updateGameResultRankSummary($versionSeason = null, $versionMajor = null, $versionMinor = null)
    {
        $this->updateSummary($versionSeason, $versionMajor, $versionMinor);
    }

    protected function getSummaryModel(): string
    {
        return GameResultRankSummary::class;
    }

    protected function getGameResults(array $params): iterable
    {
        return $this->gameResultService->getGameResultByGameRank($params);
    }

    protected function transformData(object|array $gameResult, string $minTier, int $minScore, int $versionSeason, int $versionMajor, int $versionMinor): array
    {
        return [
            'character_id' => $gameResult->character_id,
            'character_name' => $gameResult->name,
            'weapon_type' => $gameResult->weapon_type,
            'game_rank' => $gameResult->game_rank,
            'game_rank_count' => $gameResult->game_rank_count,
            'avg_mmr_gain' => $gameResult->avg_mmr_gain,
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

    public function getDetail(array $filters)
    {
        $filters['weapon_type'] = $this->replaceWeaponType($filters['weapon_type'], 'en');
        $data = GameResultRankSummary::where($filters)->orderBy('game_rank', 'asc')->get();
        $total = 0;
        foreach ($data as $item) {
            $total += $item->game_rank_count;
            $item->positive_count_percent = $item->game_rank_count ? $item->positive_count / $item->game_rank_count * 100 : 0;
            $item->negative_count_percent = $item->game_rank_count ? $item->negative_count / $item->game_rank_count * 100 : 0;
        }
        foreach ($data as $item) {
            $item->game_rank_count_percent = $total ? $item->game_rank_count / $total * 100 : 0;
            $item->weapon_type = $this->replaceWeaponType($item->weapon_type, 'ko');
        }
        return $data;
    }
}

