<?php

namespace App\Http\Controllers;

use App\Models\VersionHistory;
use App\Services\VersionedGameTableManager;
use App\Traits\ErDevTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TacticalSkillComparisonController extends Controller
{
    use ErDevTrait;

    public function index(Request $request)
    {
        $defaultTier = config('erDev.mainPageTier', 'Meteorite');
        $minTier = $request->input('min_tier', $defaultTier);
        $viewMode = $request->input('view_mode', 'combined'); // combined | by_level

        // 활성 버전 목록 조회 (드롭다운용)
        $versions = VersionHistory::active()
            ->orderBy('version_season', 'desc')
            ->orderBy('version_major', 'desc')
            ->orderBy('version_minor', 'desc')
            ->get();

        $versionOptions = $versions->map(function ($v) {
            return [
                'value' => "{$v->version_season}.{$v->version_major}.{$v->version_minor}",
                'label' => "v{$v->version_season}.{$v->version_major}.{$v->version_minor}",
            ];
        });

        // 사용자가 선택한 두 버전
        $versionA = $request->input('version_a'); // 이전 버전 (왼쪽)
        $versionB = $request->input('version_b'); // 최신 버전 (오른쪽)

        // 기본값: 최신 2개 버전
        if (!$versionA && !$versionB && $versions->count() >= 2) {
            $versionB = $versionOptions[0]['value'];
            $versionA = $versionOptions[1]['value'];
        }

        $comparisonData = [];

        if ($versionA && $versionB) {
            $byLevel = ($viewMode === 'by_level');
            $dataA = $this->fetchTacticalSkillStats($versionA, $minTier, $byLevel);
            $dataB = $this->fetchTacticalSkillStats($versionB, $minTier, $byLevel);

            // 모든 키 수집
            $allKeys = collect();
            if ($dataA) {
                $allKeys = $allKeys->merge($dataA->keys());
            }
            if ($dataB) {
                $allKeys = $allKeys->merge($dataB->keys());
            }
            $allKeys = $allKeys->unique()->sort()->values();

            foreach ($allKeys as $key) {
                $a = $dataA ? ($dataA[$key] ?? null) : null;
                $b = $dataB ? ($dataB[$key] ?? null) : null;

                $diff = null;
                if ($a && $b) {
                    $diff = [
                        'pick_rate' => round($b->pick_rate - $a->pick_rate, 2),
                        'top1_percent' => round($b->top1_percent - $a->top1_percent, 2),
                        'top2_percent' => round($b->top2_percent - $a->top2_percent, 2),
                        'top4_percent' => round($b->top4_percent - $a->top4_percent, 2),
                        'avg_mmr_gain' => round($b->avg_mmr_gain - $a->avg_mmr_gain, 1),
                        'avg_team_kill_score' => round($b->avg_team_kill_score - $a->avg_team_kill_score, 2),
                        'positive_percent' => round($b->positive_percent - $a->positive_percent, 2),
                        'negative_percent' => round($b->negative_percent - $a->negative_percent, 2),
                        'endgame_win_percent' => round(($b->endgame_win_percent ?? 0) - ($a->endgame_win_percent ?? 0), 2),
                    ];
                }

                $skillName = ($b->tactical_skill_name ?? null) ?: ($a->tactical_skill_name ?? 'Unknown');
                $level = $byLevel ? ($b->tactical_skill_level ?? $a->tactical_skill_level ?? null) : null;
                $displayName = $level ? "{$skillName} Lv.{$level}" : $skillName;

                $comparisonData[] = [
                    'key' => $key,
                    'tactical_skill_name' => $displayName,
                    'tactical_skill_name_raw' => $skillName,
                    'tactical_skill_level' => $level,
                    'version_a' => $a,
                    'version_b' => $b,
                    'diff' => $diff,
                ];
            }

            // 이름순 정렬 (같은 스킬이면 레벨 순)
            usort($comparisonData, static function ($a, $b) {
                $nameCompare = strcmp($a['tactical_skill_name_raw'], $b['tactical_skill_name_raw']);
                if ($nameCompare !== 0) {
                    return $nameCompare;
                }
                return ($a['tactical_skill_level'] ?? 0) - ($b['tactical_skill_level'] ?? 0);
            });
        }

        return view('tactical-skill-comparison', [
            'comparisonData' => $comparisonData,
            'versionOptions' => $versionOptions,
            'versionA' => $versionA,
            'versionB' => $versionB,
            'minTier' => $minTier,
            'defaultTier' => $defaultTier,
            'viewMode' => $viewMode,
        ]);
    }

    /**
     * 특정 버전의 전술스킬 종합 통계 조회
     *
     * @param bool $byLevel true이면 레벨별 분리, false이면 통합
     */
    private function fetchTacticalSkillStats(string $versionStr, string $minTier, bool $byLevel = false): ?\Illuminate\Support\Collection
    {
        $parts = explode('.', $versionStr);
        if (count($parts) !== 3) {
            return null;
        }

        $tableName = VersionedGameTableManager::getTableName('game_results_tactical_skill_summary', [
            'version_season' => $parts[0],
            'version_major' => $parts[1],
            'version_minor' => $parts[2],
        ]);

        if (!Schema::hasTable($tableName)) {
            return null;
        }

        $selectColumns = [
            'ts.id as tactical_skill_id',
            'ts.name as tactical_skill_name',
            DB::raw('SUM(gts.game_rank_count) as total_games'),
            DB::raw('SUM(CASE WHEN gts.game_rank = 1 THEN gts.game_rank_count ELSE 0 END) as top1_count'),
            DB::raw('SUM(CASE WHEN gts.game_rank <= 2 THEN gts.game_rank_count ELSE 0 END) as top2_count'),
            DB::raw('SUM(CASE WHEN gts.game_rank <= 4 THEN gts.game_rank_count ELSE 0 END) as top4_count'),
            DB::raw('ROUND(SUM(CASE WHEN gts.game_rank = 1 THEN gts.game_rank_count ELSE 0 END) / SUM(gts.game_rank_count) * 100, 2) as top1_percent'),
            DB::raw('ROUND(SUM(CASE WHEN gts.game_rank <= 2 THEN gts.game_rank_count ELSE 0 END) / SUM(gts.game_rank_count) * 100, 2) as top2_percent'),
            DB::raw('ROUND(SUM(CASE WHEN gts.game_rank <= 4 THEN gts.game_rank_count ELSE 0 END) / SUM(gts.game_rank_count) * 100, 2) as top4_percent'),
            DB::raw('ROUND(SUM(gts.avg_mmr_gain * gts.game_rank_count) / SUM(gts.game_rank_count), 1) as avg_mmr_gain'),
            DB::raw('ROUND(SUM(COALESCE(gts.avg_team_kill_score, 0) * gts.game_rank_count) / SUM(gts.game_rank_count), 2) as avg_team_kill_score'),
            DB::raw('SUM(gts.positive_count) as positive_games'),
            DB::raw('SUM(gts.negative_count) as negative_games'),
            DB::raw('ROUND(SUM(gts.positive_count) / SUM(gts.game_rank_count) * 100, 2) as positive_percent'),
            DB::raw('ROUND(SUM(gts.negative_count) / SUM(gts.game_rank_count) * 100, 2) as negative_percent'),
            DB::raw('ROUND(SUM(gts.positive_avg_mmr_gain * gts.positive_count) / NULLIF(SUM(gts.positive_count), 0), 1) as positive_avg_mmr_gain'),
            DB::raw('ROUND(SUM(gts.negative_avg_mmr_gain * gts.negative_count) / NULLIF(SUM(gts.negative_count), 0), 1) as negative_avg_mmr_gain'),
            DB::raw('ROUND(SUM(CASE WHEN gts.game_rank = 1 THEN gts.game_rank_count ELSE 0 END) / NULLIF(SUM(CASE WHEN gts.game_rank <= 2 THEN gts.game_rank_count ELSE 0 END), 0) * 100, 2) as endgame_win_percent'),
            DB::raw('COUNT(DISTINCT gts.character_id) as unique_character_count'),
        ];

        $groupByColumns = ['gts.tactical_skill_id', 'ts.id', 'ts.name'];

        if ($byLevel) {
            $selectColumns[] = 'gts.tactical_skill_level';
            $groupByColumns[] = 'gts.tactical_skill_level';
        }

        $query = DB::table("{$tableName} as gts")
            ->select($selectColumns)
            ->join('tactical_skills as ts', 'ts.id', '=', 'gts.tactical_skill_id')
            ->where('gts.min_tier', $minTier)
            ->groupBy($groupByColumns)
            ->orderBy('total_games', 'desc');

        $data = $query->get();

        // keyBy: 레벨별이면 "skillId_level", 통합이면 "skillId"
        if ($byLevel) {
            $data = $data->keyBy(function ($item) {
                return $item->tactical_skill_id . '_' . $item->tactical_skill_level;
            });
        } else {
            $data = $data->keyBy('tactical_skill_id');
        }

        // 픽률 계산
        $totalGames = $data->sum('total_games');
        foreach ($data as $item) {
            $item->pick_rate = $totalGames > 0 ? round($item->total_games / $totalGames * 100, 2) : 0;
        }

        return $data;
    }
}