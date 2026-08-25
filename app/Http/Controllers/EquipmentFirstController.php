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

        // 버전 파싱/검증 (핫픽스 포함, 예: 12.2.0b)
        $versionParts = parse_version_key($version, $defaultVersion);
        $versionSeason = $versionParts['version_season'];
        $versionMajor = $versionParts['version_major'];
        $versionMinor = $versionParts['version_minor'];
        $versionHotfix = $versionParts['version_hotfix'];

        // 캐시 키 생성
        $cacheKey = "game_equipment_first_{$minTier}_" . $versionParts['cache_key'];
        $cacheDuration = config('erDev.cacheDuration'); // 캐시 지속 시간

        // 캐시에서 데이터 조회
        $data = cache()->get($cacheKey);

        // 캐시가 없거나 데이터가 비어있으면 새로 조회
        if (!$data || empty($data['data']) || (is_countable($data['data']) && count($data['data']) === 0)) {
            // 버전별 최상위 티어 점수 동적 조회
            $versionFilters = [
                'version_season' => $versionSeason,
                'version_major' => $versionMajor,
                'version_minor' => $versionMinor,
                'version_hotfix' => $versionHotfix,
            ];
            $topRankScore = $this->rankRangeService->getTopTierMinScore($versionFilters);
            $filters = [
                'version_season' => $versionSeason,
                'version_major' => $versionMajor,
                'version_minor' => $versionMinor,
                'version_hotfix' => $versionHotfix,
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

            // 데이터가 있을 때만 캐싱
            if ($lastData && count($lastData) > 0) {
                cache()->put($cacheKey, $data, $cacheDuration);
            }
        }

        return view('first-equipment', $data);
    }

}
