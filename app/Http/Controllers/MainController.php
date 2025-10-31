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
        $minTier = 'Diamond'; // 다이아몬드 티어 고정

        // 캐시 키 생성
        $cacheKey = "patch_comparison_{$minTier}";
        $cacheDuration = config('erDev.cacheDuration', 1800); // 캐시 지속 시간

        // 데이터 조회 전체를 캐싱
        $data = cache()->remember($cacheKey, $cacheDuration, function () use ($minTier) {
            // 최신 버전과 그 이전 버전 조회
            $latestVersion = $this->patchComparisonService->getLatestVersion();

            if (!$latestVersion) {
                return [
                    'buffedCharacters' => collect(),
                    'nerfedCharacters' => collect(),
                    'latestVersion' => null,
                    'previousVersion' => null,
                ];
            }

            // 이전 버전 조회
            $previousVersion = $this->patchComparisonService->getPreviousVersion($latestVersion);

            if (!$previousVersion) {
                return [
                    'buffedCharacters' => collect(),
                    'nerfedCharacters' => collect(),
                    'latestVersion' => $latestVersion,
                    'previousVersion' => null,
                ];
            }

            // 최신 버전의 캐릭터 패치노트 조회
            $patchNotes = $this->patchComparisonService->getPatchNotes($latestVersion->id);

            if ($patchNotes->isEmpty()) {
                return [
                    'buffedCharacters' => collect(),
                    'nerfedCharacters' => collect(),
                    'latestVersion' => $latestVersion,
                    'previousVersion' => $previousVersion,
                ];
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

            return [
                'buffedCharacters' => $comparison['buffed'],
                'nerfedCharacters' => $comparison['nerfed'],
                'latestVersion' => $latestVersion,
                'previousVersion' => $previousVersion,
            ];
        });

        return view('main', $data);
    }
}
