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
        // 상세페이지 URL 의 무기는 한글(방망이)이고 스냅샷은 영문(Bat)으로 저장된다.
        // 다른 상세 조회들과 동일하게 영문으로 맞춰서 조회한다.
        $weaponType = $this->replaceWeaponType($weaponType, 'en');

        $query = DB::table('game_results_summary_snapshots')
            ->where('character_name', $characterName)
            ->where('weapon_type', $weaponType)
            ->where('min_tier', $minTier);

        if ($days !== null) {
            $query->where('captured_date', '>=', now()->subDays($days)->toDateString());
        }

        $rows = $this->onePerDate($query->orderBy('captured_date')->get());

        return [
            'points' => $rows->map(fn ($row) => [
                'date' => $row->captured_date,
                'version' => $row->version_key,
                // 누적 - 버전 시작부터 그날까지. 사이트 표시값과 일치한다.
                'meta_tier' => $row->meta_tier,
                'meta_score' => $this->num($row->meta_score),
                'game_count' => (int) $row->game_count,
                'pick_rate' => $this->num($row->game_count_percent, 2),
                'win_rate' => $this->num($row->top1_count_percent, 2),
                'top4_rate' => $this->num($row->top4_count_percent, 2),
                'avg_mmr_gain' => $this->num($row->avg_mmr_gain),
                'avg_team_kill' => $this->num($row->avg_team_kill_score, 2),
                // 일일 - 그날 게임만. 변화에 민감하지만 표본이 작다.
                'daily_meta_tier' => $row->daily_meta_tier ?? null,
                'daily_meta_score' => $this->num($row->daily_meta_score ?? null),
                'daily_game_count' => (int) ($row->daily_game_count ?? 0),
                'daily_pick_rate' => $this->num($row->daily_game_count_percent ?? null, 2),
                'daily_win_rate' => $this->num($row->daily_top1_count_percent ?? null, 2),
                'daily_top4_rate' => $this->num($row->daily_top4_count_percent ?? null, 2),
                'daily_avg_mmr_gain' => $this->num($row->daily_avg_mmr_gain ?? null),
                'daily_avg_team_kill' => $this->num($row->daily_avg_team_kill_score ?? null, 2),
            ])->all(),
            'weapon_type_ko' => $this->replaceWeaponType($weaponType, 'ko'),
        ];
    }

    /**
     * 하루에 한 점만 남긴다.
     *
     * 패치 당일에는 이전 버전과 새 버전 스냅샷이 함께 존재하고,
     * 소급 생성분과도 날짜가 겹칠 수 있다. 이때 선이 두 버전 사이를 오가며
     * 시간순이 뒤엉키므로, 그날 기준 가장 나중 버전만 남긴다.
     */
    private function onePerDate($rows)
    {
        return $rows
            ->groupBy('captured_date')
            ->map(fn ($group) => $group->sortBy(fn ($row) => $this->versionOrder($row->version_key))->last())
            ->sortKeys()
            ->values();
    }

    /**
     * 버전 키를 정렬 가능한 값으로 (문자열 비교로는 9.0.0 > 12.0.0 이 되어버린다)
     */
    private function versionOrder(string $versionKey): string
    {
        $parts = parse_version_key($versionKey);

        return sprintf(
            '%04d%04d%04d%s',
            $parts['version_season'],
            $parts['version_major'],
            $parts['version_minor'],
            $parts['version_hotfix'] ?? ''
        );
    }

    private function num($value, int $precision = 1): ?float
    {
        return $value === null ? null : round((float) $value, $precision);
    }
}
