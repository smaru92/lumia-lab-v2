<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\VersionComparisonService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 버전 간 전체 캐릭터 지표 변동 (관리자 전용)
 *
 * 메인 페이지는 패치노트에 언급된 캐릭터만 보여주므로, 간접 변동을 보려면 이 화면을 쓴다.
 */
class VersionComparisonController extends Controller
{
    public function __construct(private VersionComparisonService $versionComparisonService)
    {
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'version' => ['nullable', 'string', 'max:20'],
            'base_version' => ['nullable', 'string', 'max:20'],
            'min_tier' => ['nullable', 'string', 'max:20'],
            'group_by' => ['nullable', Rule::in(array_keys(VersionComparisonService::GROUP_BY))],
            'character_id' => ['nullable', 'integer'],
            'weapon_type' => ['nullable', 'string', 'max:30'],
            'patch_filter' => ['nullable', Rule::in(array_keys(VersionComparisonService::PATCH_FILTERS))],
            'trend' => ['nullable', Rule::in(array_keys(VersionComparisonService::TREND_FILTERS))],
            'tag' => ['nullable', 'string', 'max:50'],
            'search' => ['nullable', 'string', 'max:50'],
            'min_game_count' => ['nullable', 'integer', 'min:0'],
            'sort' => ['nullable', 'string', 'max:30'],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        return response()->json($this->versionComparisonService->compare($validated));
    }

    public function options()
    {
        return response()->json(['data' => $this->versionComparisonService->getFilterOptions()]);
    }
}
