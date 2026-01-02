<?php

namespace App\Http\Controllers;

use App\Services\PatchComparisonService;
use Illuminate\Http\Request;

class MainController extends Controller
{
    protected PatchComparisonService $patchComparisonService;

    public function __construct(PatchComparisonService $patchComparisonService)
    {
        $this->patchComparisonService = $patchComparisonService;
    }

    public function index(Request $request)
    {
        $defaultTier = config('erDev.mainPageTier'); // 환경변수에서 메인페이지 기준티어 가져오기 (Meteorite)
        $minTier = $request->input('min_tier', $defaultTier);
        $mithrilHighScore = 8000;
        $topRankScore = 8000;

        // 사용 가능한 티어 목록 (캐릭터 통계 페이지와 동일)
        $availableTiers = [
            'All' => ['name' => '전체', 'icon' => 'All'],
            'Platinum' => ['name' => '플래티넘', 'icon' => 'Platinum'],
            'Diamond' => ['name' => '다이아', 'icon' => 'Diamond'],
            'Diamond2' => ['name' => '다이아2', 'icon' => 'Diamond'],
            'Meteorite' => ['name' => '메테오라이트', 'icon' => 'Meteorite'],
            'Mithrillow' => ['name' => '미스릴', 'icon' => 'Mithril'],
            'Mithrilhigh' => ['name' => '미스릴(' . $mithrilHighScore . '+)', 'icon' => 'Mithril'],
            'Top' => ['name' => '최상위큐(' . $topRankScore . '+)', 'icon' => 'Demigod'],
        ];

        // 유효한 티어인지 확인
        if (!array_key_exists($minTier, $availableTiers)) {
            $minTier = $defaultTier;
        }

        // 캐시 키 생성
        $cacheKey = "patch_comparison_{$minTier}";
        $cacheDuration = config('erDev.cacheDuration', 1800); // 캐시 지속 시간

        // 캐시에서 데이터 조회
        $data = cache()->get($cacheKey);

        // 캐시가 없거나 latestVersion이 없으면 새로 조회 (데이터 갱신 중일 수 있음)
        if (!$data || empty($data['latestVersion'])) {
            // 최신 버전과 그 이전 버전 조회
            $latestVersion = $this->patchComparisonService->getLatestVersion();

            if (!$latestVersion) {
                // 데이터가 없으면 캐싱하지 않음
                return view('main', [
                    'buffedCharacters' => collect(),
                    'nerfedCharacters' => collect(),
                    'latestVersion' => null,
                    'previousVersion' => null,
                    'minTier' => $minTier,
                    'availableTiers' => $availableTiers,
                ]);
            }

            // 이전 버전 조회
            $previousVersion = $this->patchComparisonService->getPreviousVersion($latestVersion);

            if (!$previousVersion) {
                $data = [
                    'buffedCharacters' => collect(),
                    'nerfedCharacters' => collect(),
                    'latestVersion' => $latestVersion,
                    'previousVersion' => null,
                ];
                cache()->put($cacheKey, $data, $cacheDuration);
                return view('main', array_merge($data, [
                    'minTier' => $minTier,
                    'availableTiers' => $availableTiers,
                ]));
            }

            // 최신 버전의 캐릭터 패치노트 조회
            $patchNotes = $this->patchComparisonService->getPatchNotes($latestVersion->id);

            if ($patchNotes->isEmpty()) {
                $data = [
                    'buffedCharacters' => collect(),
                    'nerfedCharacters' => collect(),
                    'latestVersion' => $latestVersion,
                    'previousVersion' => $previousVersion,
                ];
                cache()->put($cacheKey, $data, $cacheDuration);
                return view('main', array_merge($data, [
                    'minTier' => $minTier,
                    'availableTiers' => $availableTiers,
                ]));
            }

            // 캐릭터 정보 조회
            $characterIds = $patchNotes->pluck('target_id')->unique();
            $characters = $this->patchComparisonService->getCharacters($characterIds);

            // 버프/너프 캐릭터 비교
            $comparison = $this->patchComparisonService->comparePatches(
                $latestVersion,
                $previousVersion,
                $patchNotes,
                $characters,
                $minTier
            );

            $data = [
                'buffedCharacters' => $comparison['buffed'],
                'nerfedCharacters' => $comparison['nerfed'],
                'latestVersion' => $latestVersion,
                'previousVersion' => $previousVersion,
            ];

            // 데이터가 있을 때만 캐싱
            cache()->put($cacheKey, $data, $cacheDuration);
        }

        return view('main', array_merge($data, [
            'minTier' => $minTier,
            'availableTiers' => $availableTiers,
        ]));
    }

    /**
     * 패치 비교 데이터 API
     */
    public function getPatchComparison(Request $request)
    {
        $defaultTier = config('erDev.mainPageTier');
        $minTier = $request->input('min_tier', $defaultTier);
        $mithrilHighScore = 8000;
        $topRankScore = 8000;

        // 사용 가능한 티어 목록 (캐릭터 통계 페이지와 동일)
        $availableTiers = [
            'All' => ['name' => '전체', 'icon' => 'All'],
            'Platinum' => ['name' => '플래티넘', 'icon' => 'Platinum'],
            'Diamond' => ['name' => '다이아', 'icon' => 'Diamond'],
            'Diamond2' => ['name' => '다이아2', 'icon' => 'Diamond'],
            'Meteorite' => ['name' => '메테오라이트', 'icon' => 'Meteorite'],
            'Mithrillow' => ['name' => '미스릴', 'icon' => 'Mithril'],
            'Mithrilhigh' => ['name' => '미스릴(' . $mithrilHighScore . '+)', 'icon' => 'Mithril'],
            'Top' => ['name' => '최상위큐(' . $topRankScore . '+)', 'icon' => 'Demigod'],
        ];

        // 유효한 티어인지 확인
        if (!array_key_exists($minTier, $availableTiers)) {
            $minTier = $defaultTier;
        }

        // 캐시 키 생성
        $cacheKey = "patch_comparison_{$minTier}";
        $cacheDuration = config('erDev.cacheDuration', 1800);

        // 캐시에서 데이터 조회
        $data = cache()->get($cacheKey);

        // 캐시가 없거나 latestVersion이 없으면 새로 조회
        if (!$data || empty($data['latestVersion'])) {
            $latestVersion = $this->patchComparisonService->getLatestVersion();

            if (!$latestVersion) {
                return response()->json([
                    'buffedCharacters' => [],
                    'nerfedCharacters' => [],
                    'latestVersion' => null,
                    'previousVersion' => null,
                    'minTier' => $minTier,
                ]);
            }

            $previousVersion = $this->patchComparisonService->getPreviousVersion($latestVersion);

            if (!$previousVersion) {
                return response()->json([
                    'buffedCharacters' => [],
                    'nerfedCharacters' => [],
                    'latestVersion' => $this->formatVersion($latestVersion),
                    'previousVersion' => null,
                    'minTier' => $minTier,
                ]);
            }

            $patchNotes = $this->patchComparisonService->getPatchNotes($latestVersion->id);

            if ($patchNotes->isEmpty()) {
                $data = [
                    'buffedCharacters' => collect(),
                    'nerfedCharacters' => collect(),
                    'latestVersion' => $latestVersion,
                    'previousVersion' => $previousVersion,
                ];
                cache()->put($cacheKey, $data, $cacheDuration);
            } else {
                $characterIds = $patchNotes->pluck('target_id')->unique();
                $characters = $this->patchComparisonService->getCharacters($characterIds);

                $comparison = $this->patchComparisonService->comparePatches(
                    $latestVersion,
                    $previousVersion,
                    $patchNotes,
                    $characters,
                    $minTier
                );

                $data = [
                    'buffedCharacters' => $comparison['buffed'],
                    'nerfedCharacters' => $comparison['nerfed'],
                    'latestVersion' => $latestVersion,
                    'previousVersion' => $previousVersion,
                ];

                cache()->put($cacheKey, $data, $cacheDuration);
            }
        }

        // API 응답용으로 데이터 변환
        return response()->json([
            'buffedCharacters' => $this->formatCharactersForApi($data['buffedCharacters']),
            'nerfedCharacters' => $this->formatCharactersForApi($data['nerfedCharacters']),
            'latestVersion' => $this->formatVersion($data['latestVersion']),
            'previousVersion' => $this->formatVersion($data['previousVersion']),
            'minTier' => $minTier,
        ]);
    }

    /**
     * 버전 정보 포맷팅
     */
    private function formatVersion($version)
    {
        if (!$version) return null;

        return [
            'version_season' => $version->version_season,
            'version_major' => $version->version_major,
            'version_minor' => $version->version_minor,
            'start_date' => $version->start_date->format('Y-m-d'),
            'full_version' => "{$version->version_season}.{$version->version_major}.{$version->version_minor}",
        ];
    }

    /**
     * 캐릭터 데이터 API 응답용 포맷팅
     */
    private function formatCharactersForApi($characters)
    {
        return $characters->map(function ($item) {
            return [
                'character_id' => $item['character_id'],
                'character_name' => $item['character_name'],
                'weapon_type' => $item['weapon_type'],
                'weapon_type_en' => $item['weapon_type_en'] ?? $item['weapon_type'],
                'meta_score_diff' => $item['meta_score_diff'],
                'pick_rate_diff' => $item['pick_rate_diff'],
                'win_rate_diff' => $item['win_rate_diff'],
                'avg_mmr_gain_diff' => $item['avg_mmr_gain_diff'],
                'top4_rate_diff' => $item['top4_rate_diff'],
                'previous' => [
                    'meta_tier' => $item['previous']->meta_tier,
                    'meta_score' => $item['previous']->meta_score,
                    'game_count_percent' => $item['previous']->game_count_percent,
                    'avg_mmr_gain' => $item['previous']->avg_mmr_gain,
                    'top1_count_percent' => $item['previous']->top1_count_percent,
                    'top2_count_percent' => $item['previous']->top2_count_percent,
                    'top4_count_percent' => $item['previous']->top4_count_percent,
                    'endgame_win_percent' => $item['previous']->endgame_win_percent,
                    'avg_team_kill_score' => $item['previous']->avg_team_kill_score,
                ],
                'latest' => [
                    'meta_tier' => $item['latest']->meta_tier,
                    'meta_score' => $item['latest']->meta_score,
                    'game_count_percent' => $item['latest']->game_count_percent,
                    'avg_mmr_gain' => $item['latest']->avg_mmr_gain,
                    'top1_count_percent' => $item['latest']->top1_count_percent,
                    'top2_count_percent' => $item['latest']->top2_count_percent,
                    'top4_count_percent' => $item['latest']->top4_count_percent,
                    'endgame_win_percent' => $item['latest']->endgame_win_percent,
                    'avg_team_kill_score' => $item['latest']->avg_team_kill_score,
                ],
            ];
        })->values()->toArray();
    }
}
