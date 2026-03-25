<?php

namespace App\Services;

use App\Models\GameResultSynergySummary;
use App\Models\VersionHistory;
use App\Traits\ErDevTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GameResultSynergySummaryService extends BaseSummaryService
{
    use ErDevTrait;

    public function __construct()
    {
        parent::__construct('updateGameResultSynergySummary');
    }

    public function updateGameResultSynergySummary($versionSeason = null, $versionMajor = null, $versionMinor = null)
    {
        $this->updateSummary($versionSeason, $versionMajor, $versionMinor);
    }

    protected function getSummaryModel(): string
    {
        return GameResultSynergySummary::class;
    }

    protected function getSummaryTableBaseName(): string
    {
        return 'game_results_synergy_summary';
    }

    protected function ensureTableExists(string $tableName): void
    {
        $this->versionedTableManager->ensureGameResultSynergySummaryTableExists($tableName);
    }

    protected function getGameResults(array $params): iterable
    {
        return $this->gameResultService->getGameResultSynergy($params);
    }

    protected function transformData(object|array $gameResult, string $minTier, int $minScore): array
    {
        return [
            'character_id' => $gameResult['characterId'],
            'character_name' => $gameResult['characterName'],
            'weapon_type' => $gameResult['weaponType'],
            'synergy_character_id' => $gameResult['synergyCharacterId'],
            'synergy_character_name' => $gameResult['synergyCharacterName'],
            'synergy_weapon_type' => $gameResult['synergyWeaponType'],
            'min_tier' => $minTier,
            'min_score' => $minScore,
            'game_count' => $gameResult['gameCount'],
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
            'positive_avg_mmr_gain' => $gameResult['positiveAvgMmrGain'],
            'negative_avg_mmr_gain' => $gameResult['negativeAvgMmrGain'],
            'avg_team_kill_score' => $gameResult['avgTeamKillScore'],
            'updated_at' => now(),
            'created_at' => now(),
        ];
    }

    protected function getVersionedTableName(array $filters): string
    {
        $versionSeason = $filters['version_season'] ?? null;
        $versionMajor = $filters['version_major'] ?? null;
        $versionMinor = $filters['version_minor'] ?? null;

        if (!$versionSeason || !$versionMajor || !$versionMinor) {
            $latestVersion = VersionHistory::active()->latest('created_at')->first();
            $versionSeason = $versionSeason ?? $latestVersion->version_season;
            $versionMajor = $versionMajor ?? $latestVersion->version_major;
            $versionMinor = $versionMinor ?? $latestVersion->version_minor;
        }

        return VersionedGameTableManager::getTableName($this->getSummaryTableBaseName(), [
            'version_season' => $versionSeason,
            'version_major' => $versionMajor,
            'version_minor' => $versionMinor,
        ]);
    }

    public function getList(array $filters)
    {
        $tableName = $this->getVersionedTableName($filters);

        if (!Schema::hasTable($tableName)) {
            return collect();
        }

        unset($filters['version_season'], $filters['version_major'], $filters['version_minor']);

        return DB::table($tableName)
            ->where($filters)
            ->where('game_count', '>=', 10)
            ->orderBy('game_count', 'desc')
            ->get();
    }
}
