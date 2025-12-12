<?php

namespace App\Services;

use App\Traits\ErDevTrait;

class TraitMainService
{
    use ErDevTrait;
    protected RankRangeService $rankRangeService;
    protected VersionHistoryService $versionHistoryService;
    protected GameResultTraitMainSummaryService $gameResultTraitMainSummaryService;

    public function __construct(
        GameResultTraitMainSummaryService $gameResultTraitMainSummaryService,
        VersionHistoryService $versionHistoryService,
        RankRangeService $rankRangeService
    )
    {
        $this->gameResultTraitMainSummaryService = $gameResultTraitMainSummaryService;
        $this->versionHistoryService = $versionHistoryService;
        $this->rankRangeService = $rankRangeService;
    }

    /**
     * 특성 메인 통계 요약 조회
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getGameResultTraitMainSummary(array $filters = [])
    {
        $result = $this->gameResultTraitMainSummaryService->getList($filters);
        foreach ($result as $item) {
            // trait_category가 이미 한글이므로 그대로 사용
            $item['trait_category_ko'] = $item['trait_category'];
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

    /**
     * 특성 카테고리 한글 변환
     * @param string $category
     * @return string
     */
    private function replaceTraitCategory($category)
    {
        $mapping = [
            'Destruction' => '파괴',
            'Chaos' => '혼돈',
            'Support' => '지원',
            'Fortification' => '저항',
        ];
        return $mapping[$category] ?? $category;
    }
}
