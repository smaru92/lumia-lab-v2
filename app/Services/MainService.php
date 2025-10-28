<?php

namespace App\Services;

use App\Models\GameResult;
use App\Models\RankRange;
use App\Models\VersionHistory;
use App\Traits\ErDevTrait;
use Faker\Core\Version;

class MainService
{
    use ErDevTrait;
    protected GameResultSummaryService $gameResultSummaryService;
    protected RankRangeService $rankRangeService;
    protected VersionHistoryService $versionHistoryService;
    protected GameResultRankSummaryService $gameResultRankSummaryService;
    protected GameResultEquipmentSummaryService $gameResultEquipmentSummaryService;
    protected GameResultTraitSummaryService $gameResultTraitSummaryService;
    protected GameResultTacticalSkillSummaryService $gameResultTacticalSkillSummaryService;

    public function __construct(
        GameResultSummaryService $gameResultSummaryService,
        GameResultRankSummaryService $gameResultRankSummaryService,
        GameResultEquipmentSummaryService $gameResultEquipmentSummaryService,
        GameResultTraitSummaryService $gameResultTraitSummaryService,
        GameResultTacticalSkillSummaryService $gameResultTacticalSkillSummaryService,
        VersionHistoryService $versionHistoryService,
        RankRangeService $rankRangeService
    )
    {
        $this->gameResultSummaryService = $gameResultSummaryService;
        $this->gameResultRankSummaryService = $gameResultRankSummaryService;
        $this->gameResultTacticalSkillSummaryService = $gameResultTacticalSkillSummaryService;
        $this->gameResultTraitSummaryService = $gameResultTraitSummaryService;
        $this->gameResultEquipmentSummaryService = $gameResultEquipmentSummaryService;
        $this->versionHistoryService = $versionHistoryService;
        $this->rankRangeService = $rankRangeService;
    }

    /**
     * @param array $filters
     * @return array[
     * 'gameCount' => int,
     * 'positiveCount' => int,
     * 'negativeCount' => int,
     * 'avgMmrGain' => float,
     * 'top1Count' => int,
     * 'top2Count' => int,
     * 'top4Count' => int,
     * 'avgPositiveMmrGain' => float,
     * 'avgNegativeMmrGain' => float
     * 'gameCountPercent' => float
     * 'positiveCountPercent' => float
     * 'negativeCountPercent' => float
     * 'top1CountPercent' => float
     * 'top2CountPercent' => float
     * 'top4CountPercent' => float
     * ]
     */
    public function getGameResultSummary(array $filters = [])
    {
        $result = $this->gameResultSummaryService->getList($filters);
        foreach ($result as $gameResult) {
            $gameResult['weapon_type_en'] = $gameResult['weapon_type'];
            $gameResult['weapon_type'] = $this->replaceWeaponType($gameResult['weapon_type']);
        }
        return $result;
    }
    public function getGameResultSummaryDetail(array $filters = [])
    {
        $filters['weapon_type'] = $this->replaceWeaponType($filters['weapon_type'], 'en');
        $result = $this->gameResultSummaryService->getDetail($filters);
        if ($result) {
            $result->weapon_type_en = $result->weapon_type;
            $result->weapon_type = $this->replaceWeaponType($result->weapon_type);
        }
        return $result;
    }

    public function getGameResultSummaryDetailBulk(array $filters, array $tierRange)
    {
        $filters['weapon_type'] = $this->replaceWeaponType($filters['weapon_type'], 'en');
        $results = $this->gameResultSummaryService->getDetailBulk($filters, $tierRange);

        // 각 티어에 대해 tier_name 추가
        foreach ($results as $tier => $result) {
            if ($result) {
                $result->tier_name = $this->replaceTierName($tier);
                $result->weapon_type_en = $result->weapon_type;
                $result->weapon_type = $this->replaceWeaponType($result->weapon_type);
            }
        }

        return $results;
    }

    public function getGameResultRankSummary(array $filters = [])
    {
        return $this->gameResultRankSummaryService->getDetail($filters);
    }
    public function getGameResultTacticalSkillSummary(array $filters = [])
    {
        return $this->gameResultTacticalSkillSummaryService->getDetail($filters);
    }
    public function getGameResultEquipmentSummary(array $filters = [])
    {
        return $this->gameResultEquipmentSummaryService->getDetail($filters);
    }
    public function getGameResultTraitSummary(array $filters = [])
    {
        return $this->gameResultTraitSummaryService->getDetail($filters);
    }

    public function getLatestVersion()
    {
        return $this->versionHistoryService->getLatestVersion();
    }

    public function getLatestVersionList()
    {
        $versions = $this->versionHistoryService->getLatestVersionList();
        $result = [];
        foreach ($versions as $version) {
            $result[] = $version->version_season . '.' . $version->version_major . '.' . $version->version_minor;
        }

        return $result;
    }
}
