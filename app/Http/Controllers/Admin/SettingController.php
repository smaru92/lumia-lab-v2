<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VersionHistory;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 사이트 운영 설정 (기본 버전 / 기본 티어)
 */
class SettingController extends Controller
{
    /** 통계 화면에서 고를 수 있는 티어 목록 (partials/filter-dropdowns.blade.php 와 동일) */
    private const TIERS = [
        'All', 'Platinum', 'Diamond', 'Diamond2', 'Meteorite', 'Mithrillow', 'Mithrilhigh', 'Top',
    ];

    public function __construct(private SettingService $settingService)
    {
    }

    public function index()
    {
        return response()->json([
            'data' => $this->settingService->toArray(),
            'options' => [
                'tiers' => self::TIERS,
                'versions' => $this->versionOptions(),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'default_version_mode' => ['required', Rule::in([SettingService::MODE_AUTO, SettingService::MODE_MANUAL])],
            'default_version' => ['nullable', 'string', 'max:20'],
            'default_version_delay_hours' => ['required', 'integer', 'min:0', 'max:720'],
            'default_tier' => ['required', Rule::in(self::TIERS)],
            'main_page_tier' => ['required', Rule::in(self::TIERS)],
        ]);

        // 수동 모드인데 버전을 고르지 않으면 자동 모드와 구분이 안 되므로 막는다.
        if ($validated['default_version_mode'] === SettingService::MODE_MANUAL && empty($validated['default_version'])) {
            return response()->json([
                'message' => '수동 모드에서는 기본 버전을 선택해야 합니다.',
                'errors' => ['default_version' => ['기본 버전을 선택해주세요.']],
            ], 422);
        }

        $this->settingService->set(SettingService::KEY_DEFAULT_VERSION_MODE, $validated['default_version_mode']);
        $this->settingService->set(SettingService::KEY_DEFAULT_VERSION, $validated['default_version'] ?? null);
        $this->settingService->set(SettingService::KEY_DEFAULT_VERSION_DELAY_HOURS, (string) $validated['default_version_delay_hours']);
        $this->settingService->set(SettingService::KEY_DEFAULT_TIER, $validated['default_tier']);
        $this->settingService->set(SettingService::KEY_MAIN_PAGE_TIER, $validated['main_page_tier']);

        $this->settingService->flushCache();

        return response()->json([
            'data' => $this->settingService->toArray(),
            'options' => [
                'tiers' => self::TIERS,
                'versions' => $this->versionOptions(),
            ],
        ]);
    }

    /**
     * 수동 모드에서 고를 수 있는 버전 목록 (최근 20개)
     */
    private function versionOptions(): array
    {
        return VersionHistory::query()
            ->orderByDesc('start_date')
            ->limit(20)
            ->get()
            ->map(fn (VersionHistory $version) => [
                'value' => $version->version_key,
                'label' => $version->version_key,
                'start_date' => $version->start_date?->format('Y-m-d H:i'),
            ])
            ->values()
            ->all();
    }
}
