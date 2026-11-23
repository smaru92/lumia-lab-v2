<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\EquipmentStatService;
use App\Services\TopRankStatService;
use Illuminate\Http\Request;

/**
 * 장비별 지표 (관리자 전용)
 */
class EquipmentStatController extends Controller
{
    public function __construct(
        private EquipmentStatService $equipmentStatService,
        private TopRankStatService $topRankStatService,
    ) {
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'version' => ['nullable', 'string', 'max:20'],
            'min_tier' => ['nullable', 'string', 'max:20'],
            'character_id' => ['nullable', 'integer'],
            'weapon_type' => ['nullable', 'string', 'max:30'],
            'group' => ['nullable', 'string', 'max:30'],
            'sub_group' => ['nullable', 'string', 'max:30'],
            'search' => ['nullable', 'string', 'max:50'],
            'min_game_count' => ['nullable', 'integer', 'min:0'],
            'min_character_game_count' => ['nullable', 'integer', 'min:1'],
        ]);

        $result = $this->equipmentStatService->getStats($validated);

        return response()->json([
            'data' => $result['data'],
            'meta' => [
                'table' => $result['table'],
                'exists' => $result['exists'],
                'count' => count($result['data']),
                'weapon_types' => $result['weapon_types'] ?? [],
            ],
        ]);
    }

    /**
     * 필터 선택지는 TOP4 지표와 같다 (버전/티어/캐릭터/부위/등급)
     */
    public function options()
    {
        $options = $this->topRankStatService->getFilterOptions();

        return response()->json(['data' => [
            'versions' => $options['versions'],
            'tiers' => $options['tiers'],
            'characters' => $options['characters'],
            'groups' => $options['groups']['equipment'] ?? [],
            'sub_groups' => $options['sub_groups']['equipment'] ?? [],
        ]]);
    }
}
