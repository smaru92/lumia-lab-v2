<?php

namespace App\Http\Controllers;

use App\Services\EquipmentMainService;
use App\Services\RankRangeService;
use Illuminate\Http\Request;

class EquipmentController
{
    protected EquipmentMainService $equipmentMainService;
    protected RankRangeService $rankRangeService;
    protected int $versionSeason;
    protected int $versionMajor;
    protected int $versionMinor;
    protected string $minTier;

    public function __construct(EquipmentMainService $equipmentMainService, RankRangeService $rankRangeService)
    {
        $this->equipmentMainService = $equipmentMainService;
        $this->rankRangeService = $rankRangeService;
        $this->equipmentMainService->getLatestVersion();
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

        // 버전 형식 검증
        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            $version = $defaultVersion;
        }

        $version =  explode('.', $version);
        $versionSeason = $version[0];
        $versionMajor = $version[1];
        $versionMinor = $version[2];

        // 추가 검증 - 숫자 범위 확인
        if (!is_numeric($versionSeason) || !is_numeric($versionMajor) || !is_numeric($versionMinor) ||
            $versionSeason < 0 || $versionSeason > 999 ||
            $versionMajor < 0 || $versionMajor > 999 ||
            $versionMinor < 0 || $versionMinor > 999) {
            // 잘못된 버전이면 기본값 사용
            $version =  explode('.', $defaultVersion);
            $versionSeason = $version[0];
            $versionMajor = $version[1];
            $versionMinor = $version[2];
        }

        // 캐시 키 생성
        $cacheKey = "game_equipment_{$minTier}_" . implode('_', $version);
        $cacheDuration = config('erDev.cacheDuration'); // 캐시 지속 시간

        // 캐시에서 데이터 조회
        $data = cache()->get($cacheKey);

        // 캐시가 없거나 데이터가 비어있으면 새로 조회
        if (!$data || empty($data['data']) || (is_countable($data['data']) && count($data['data']) === 0)) {
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
            $lastData = $this->equipmentMainService->getGameResultEquipmentMainSummary($filters);
            if ($lastData->first()) {
                $lastUpdate = $lastData->first()->created_at ?? null;
            } else {
                $lastUpdate = null;
            }

            $versions = $this->equipmentMainService->getLatestVersionList();

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

        return view('equipment', $data);
    }

}
