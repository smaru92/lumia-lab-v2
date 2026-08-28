<?php

namespace App\Services;

use App\Traits\ErDevTrait;
use Illuminate\Support\Facades\DB;

/**
 * 캐릭터 지표 추이 조회
 *
 * game_results_summary_snapshots 에 하루 1건씩 쌓인 스냅샷을 읽는다.
 * 집계 테이블을 다시 훑지 않으므로 조회가 가볍다.
 */
class SummaryTrendService
{
    use ErDevTrait;

    /** 캐릭터 상세(공개)에서 보여주는 기간 */
    public const PUBLIC_DAYS = 15;

    /**
     * 캐릭터 x 무기 x 티어의 일자별 추이
     *
     * @param int|null $days null 이면 전체 기간 (관리자용)
     */
    public function getTrend(string $characterName, string $weaponType, string $minTier, ?int $days = self::PUBLIC_DAYS): array
    {
        $query = DB::table('game_results_summary_snapshots')
            ->where('character_name', $characterName)
            ->where('weapon_type', $weaponType)
            ->where('min_tier', $minTier);

        if ($days !== null) {
            $query->where('captured_date', '>=', now()->subDays($days)->toDateString());
        }

        $rows = $query->orderBy('captured_date')->get();

        return [
            'points' => $rows->map(fn ($row) => [
                'date' => $row->captured_date,
                'version' => $row->version_key,
                'meta_tier' => $row->meta_tier,
                'meta_score' => $this->num($row->meta_score),
                'game_count' => (int) $row->game_count,
                'pick_rate' => $this->num($row->game_count_percent, 2),
                'win_rate' => $this->num($row->top1_count_percent, 2),
                'top4_rate' => $this->num($row->top4_count_percent, 2),
                'avg_mmr_gain' => $this->num($row->avg_mmr_gain),
                'avg_team_kill' => $this->num($row->avg_team_kill_score, 2),
            ])->all(),
            'weapon_type_ko' => $this->replaceWeaponType($weaponType, 'ko'),
        ];
    }

    private function num($value, int $precision = 1): ?float
    {
        return $value === null ? null : round((float) $value, $precision);
    }
}
