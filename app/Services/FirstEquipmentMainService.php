<?php

namespace App\Services;

use App\Models\GameResult;
use App\Models\RankRange;
use App\Models\VersionHistory;
use App\Traits\ErDevTrait;
use Faker\Core\Version;

class FirstEquipmentMainService
{
    use ErDevTrait;
    protected RankRangeService $rankRangeService;
    protected VersionHistoryService $versionHistoryService;
    protected GameResultFirstEquipmentMainSummaryService $gameResultFirstEquipmentMainSummaryService;

    public function __construct(
        GameResultFirstEquipmentMainSummaryService $gameResultFirstEquipmentMainSummaryService,
        VersionHistoryService $versionHistoryService,
        RankRangeService $rankRangeService
    )
    {
        $this->gameResultFirstEquipmentMainSummaryService = $gameResultFirstEquipmentMainSummaryService;
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
    public function getGameResultFirstEquipmentMainSummary(array $filters = [])
    {
        $result = $this->gameResultFirstEquipmentMainSummaryService->getList($filters);
        foreach ($result as $gameResult) {
            $gameResult->item_grade_en = $gameResult->item_grade;
            $gameResult->item_grade = $this->replaceItemGrade($gameResult->item_grade);
            $gameResult->item_type2_en = $gameResult->item_type2;
            $gameResult->item_type2 = $this->replaceItemType2($gameResult->item_type2);
        }
        return $result;
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
