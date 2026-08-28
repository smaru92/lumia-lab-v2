<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SummaryTrendService;
use App\Traits\ErDevTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 캐릭터 지표 추이 (관리자 전용, 전체 기간)
 *
 * 캐릭터 상세는 최근 15일만 보여주고, 그보다 긴 기간은 여기서 본다.
 */
class SummaryTrendController extends Controller
{
    use ErDevTrait;

    public function __construct(private SummaryTrendService $trendService)
    {
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'character_name' => ['required', 'string', 'max:50'],
            'weapon_type' => ['required', 'string', 'max:30'],
            'min_tier' => ['nullable', 'string', 'max:20'],
            'days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);

        $trend = $this->trendService->getTrend(
            $validated['character_name'],
            $validated['weapon_type'],
            $validated['min_tier'] ?? 'Diamond',
            // days 를 주지 않으면 전체 기간
            $validated['days'] ?? null
        );

        return response()->json($trend);
    }

    /**
     * 스냅샷에 실제로 존재하는 조합만 선택지로 준다
     */
    public function options(Request $request)
    {
        $characterName = $request->input('character_name');

        $characters = DB::table('game_results_summary_snapshots')
            ->distinct()->orderBy('character_name')->pluck('character_name')
            ->filter()
            ->map(fn ($n) => ['value' => $n, 'label' => $n])
            ->values()->all();

        $weaponQuery = DB::table('game_results_summary_snapshots')->distinct();
        if ($characterName) {
            $weaponQuery->where('character_name', $characterName);
        }
        $weapons = $weaponQuery->orderBy('weapon_type')->pluck('weapon_type')
            ->filter()
            ->map(fn ($w) => ['value' => $w, 'label' => $this->replaceWeaponType($w, 'ko')])
            ->values()->all();

        return response()->json([
            'data' => [
                'characters' => $characters,
                'weapons' => $weapons,
                'tiers' => ['All', 'Platinum', 'Diamond', 'Diamond2', 'Meteorite', 'Mithrillow', 'Mithrilhigh', 'Top'],
                'range' => DB::table('game_results_summary_snapshots')
                    ->selectRaw('MIN(captured_date) as first_date, MAX(captured_date) as last_date, COUNT(DISTINCT captured_date) as days')
                    ->first(),
            ],
        ]);
    }
}
