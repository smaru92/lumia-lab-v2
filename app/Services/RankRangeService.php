<?php

namespace App\Services;

use App\Models\RankRange;
use App\Models\VersionHistory;
use Illuminate\Support\Facades\DB;

class RankRangeService
{
    /**
     * 해당 티어의 가장 낮은 점수를 반환한다.
     * @param $minTier
     * @param $tierNumber
     * @param array $versionFilters 버전 필터 (version_season, version_major, version_minor)
     * @return int
     */
    public function getMinScore($minTier, $tierNumber = null, array $versionFilters = [])
    {
        $result = RankRange::select(
            DB::raw("MIN(min_score) as min_score")
        )->where('grade1', $minTier);
        
        if ($tierNumber !== null) {
            $result = $result->where('grade2', $tierNumber);
        }
        
        // 1. 먼저 지정된 버전으로 조회 시도
        if (!empty($versionFilters)) {
            $versionResult = clone $result;
            if (isset($versionFilters['version_season'])) {
                $versionResult = $versionResult->where('version_season', $versionFilters['version_season']);
            }
            if (isset($versionFilters['version_major'])) {
                $versionResult = $versionResult->where('version_major', $versionFilters['version_major']);
            }
            if (isset($versionFilters['version_minor'])) {
                $versionResult = $versionResult->where('version_minor', $versionFilters['version_minor']);
            }
            
            $rankRange = $versionResult->first();
            
            // 특정 버전 데이터가 존재하면 반환
            if ($rankRange) {
                return $rankRange->min_score;
            }
        }
        
        // 2. 특정 버전 데이터가 없으면 기본값(NULL 버전) 사용
        $defaultResult = clone $result;
        $defaultResult = $defaultResult
            ->whereNull('version_season')
            ->whereNull('version_major')
            ->whereNull('version_minor');
        $rankRange = $defaultResult->first();
        
        return $rankRange ? $rankRange->min_score : 0;
    }

    /**
     * 최상위 티어(Top)의 최소 점수를 반환한다.
     * @param array $versionFilters 버전 필터 (version_season, version_major, version_minor)
     * @return int
     */
    public function getTopTierMinScore(array $versionFilters = [])
    {
        // 1. 먼저 지정된 버전으로 조회 시도
        if (!empty($versionFilters)) {
            $versionResult = RankRange::select(
                DB::raw("MIN(min_score) as min_score")
            )->whereIn('grade1', ['Mithril', 'Demigod', 'Eternity']); // 최상위 티어들
            
            if (isset($versionFilters['version_season'])) {
                $versionResult = $versionResult->where('version_season', $versionFilters['version_season']);
            }
            if (isset($versionFilters['version_major'])) {
                $versionResult = $versionResult->where('version_major', $versionFilters['version_major']);
            }
            if (isset($versionFilters['version_minor'])) {
                $versionResult = $versionResult->where('version_minor', $versionFilters['version_minor']);
            }
            
            $rankRange = $versionResult->first();
            
            // 특정 버전 데이터가 존재하면 반환
            if ($rankRange && $rankRange->min_score) {
                return $rankRange->min_score;
            }
        }
        
        // 2. 특정 버전 데이터가 없으면 기본값(NULL 버전) 사용
        $defaultResult = RankRange::select(
            DB::raw("MIN(min_score) as min_score")
        )->whereIn('grade1', ['Mithril', 'Demigod', 'Eternity']) // 최상위 티어들
            ->whereNull('version_season')
            ->whereNull('version_major')
            ->whereNull('version_minor');
        $rankRange = $defaultResult->first();
        
        return $rankRange && $rankRange->min_score ? $rankRange->min_score : config('erDev.topRankScore', 8000);
    }
}
