<?php

namespace App\Services;

use App\Models\RankRange;
use App\Models\VersionHistory;
use Illuminate\Support\Facades\DB;

class RankRangeService
{
    /**
     * 해당 티어의 가장 낮은 점수를 반환한다.
     * @param $tierName
     * @return int
     */
    public function getMinScore($minTier, $tierNumber = null)
    {
        $result = RankRange::select(
            DB::raw("MIN(min_score) as min_score")
        )->where('grade1', $minTier);
        if ($tierNumber !== null) {
            $result = $result->where('grade2', $tierNumber);
        }
        return $result->first()->min_score;
    }
}
