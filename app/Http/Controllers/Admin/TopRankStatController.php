<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TopRankStatService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * TOP4 상세 지표 (관리자 전용)
 */
class TopRankStatController extends Controller
{
    public function __construct(private TopRankStatService $topRankStatService)
    {
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'type' => ['nullable', Rule::in(array_keys(TopRankStatService::TYPES))],
            'version' => ['nullable', 'string', 'max:20'],
            'min_tier' => ['nullable', 'string', 'max:20'],
            'character_id' => ['nullable', 'integer'],
            'weapon_type' => ['nullable', 'string', 'max:30'],
            'search' => ['nullable', 'string', 'max:50'],
            'min_game_count' => ['nullable', 'integer', 'min:0'],
        ]);

        $result = $this->topRankStatService->getStats($validated);

        return response()->json([
            'data' => $result['data'],
            'meta' => [
                'table' => $result['table'],
                'exists' => $result['exists'],
                'count' => count($result['data']),
            ],
        ]);
    }

    public function options()
    {
        return response()->json(['data' => $this->topRankStatService->getFilterOptions()]);
    }
}
