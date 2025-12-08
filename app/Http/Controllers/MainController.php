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
        $minTier = config('erDev.mainPageTier'); // 환경변수에서 메인페이지 기준티어 가져오기

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
                return view('main', $data);
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
                return view('main', $data);
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

        return view('main', $data);
    }
}
