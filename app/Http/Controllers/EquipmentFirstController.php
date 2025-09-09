<?php

namespace App\Http\Controllers;

use App\Services\FirstEquipmentMainService;
use App\Services\RankRangeService;
use Illuminate\Http\Request;

class EquipmentFirstController
{
    protected FirstEquipmentMainService $firstEquipmentMainService;
    protected RankRangeService $rankRangeService;
    protected int $versionSeason;
    protected int $versionMajor;
    protected int $versionMinor;
    protected string $minTier;

    public function __construct(FirstEquipmentMainService $firstEquipmentMainService, RankRangeService $rankRangeService)
    {
        $this->firstEquipmentMainService = $firstEquipmentMainService;
        $this->rankRangeService = $rankRangeService;
        $this->firstEquipmentMainService->getLatestVersion();
        $this->versionSeason = 0;
        $this->versionMajor = 0;
        $this->versionMinor = 0;
        $this->minTier = 'diamond';
    }

    public function index(Request $request)
    {
        $defaultTier = config('erDev.defaultTier');
        $defaultVersion = config('erDev.defaultVersion');
        $minTier = $request->input('min_tier', $defaultTier);
        $version = $request->input('version', $defaultVersion);
        $version =  explode('.', $version);
        $versionSeason = $version[0];
        $versionMajor = $version[1];
        $versionMinor = $version[2];
        
        // 버전별 최상위 티어 점수 동적 조회
        $versionFilters = [
            'version_season' => $versionSeason,
            'version_major' => $versionMajor,
            'version_minor' => $versionMinor
        ];
        $topRankScore = $this->rankRangeService->getTopTierMinScore($versionFilters);
        $filters = [
            'version_season' => $versionSeason,
            'version_major' => $versionMajor,
            'version_minor' => $versionMinor,
            'min_tier' => $minTier,
        ];
        $lastData = $this->firstEquipmentMainService->getGameResultFirstEquipmentMainSummary($filters);
        if ($lastData->first()) {
            $lastUpdate = $lastData->first()->created_at ?? null;
        } else {
            $lastUpdate = null;
        }

        $versions = $this->firstEquipmentMainService->getLatestVersionList();

        $data = [
            'lastUpdate' => $lastUpdate,
            'defaultVersion' => $defaultVersion,
            'defaultTier' => $defaultTier,
            'topRankScore' => $topRankScore,
            'data' => $lastData,
            'versions' => $versions,
        ];
        return view('first-equipment', $data);
    }

}
