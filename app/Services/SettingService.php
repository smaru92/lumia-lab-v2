<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\VersionHistory;
use Illuminate\Support\Facades\Cache;

/**
 * 사이트 운영 설정 조회/저장
 *
 * 기본 버전과 기본 티어는 .env 대신 관리자 화면에서 조절한다.
 * .env 값(config('erDev.*'))은 설정이 비어 있을 때의 폴백으로만 남는다.
 */
class SettingService
{
    public const KEY_DEFAULT_VERSION_MODE = 'default_version_mode';
    public const KEY_DEFAULT_VERSION = 'default_version';
    public const KEY_DEFAULT_VERSION_DELAY_HOURS = 'default_version_delay_hours';
    public const KEY_DEFAULT_TIER = 'default_tier';
    public const KEY_MAIN_PAGE_TIER = 'main_page_tier';

    public const MODE_AUTO = 'auto';
    public const MODE_MANUAL = 'manual';

    /** 새 버전이 등장하고 기본 버전으로 승격되기까지의 기본 대기 시간 */
    public const DEFAULT_DELAY_HOURS = 24;

    /** 자동 모드에서 계산한 기본 버전 캐시 (버전 승격은 시간 기준이라 짧게 잡는다) */
    private const AUTO_VERSION_CACHE_KEY = 'site_settings_auto_default_version';
    private const AUTO_VERSION_CACHE_TTL = 300;

    public function get(string $key, $default = null)
    {
        $value = Setting::all_values()[$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    public function set(string $key, $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::AUTO_VERSION_CACHE_KEY);
    }

    /**
     * 캐릭터/상세 페이지 기본 티어
     */
    public function defaultTier(): string
    {
        return (string) $this->get(self::KEY_DEFAULT_TIER, config('erDev.defaultTier', 'Platinum'));
    }

    /**
     * 메인 페이지 기준 티어
     */
    public function mainPageTier(): string
    {
        return (string) $this->get(self::KEY_MAIN_PAGE_TIER, config('erDev.mainPageTier', 'Diamond'));
    }

    public function defaultVersionMode(): string
    {
        $mode = $this->get(self::KEY_DEFAULT_VERSION_MODE, self::MODE_AUTO);

        return $mode === self::MODE_MANUAL ? self::MODE_MANUAL : self::MODE_AUTO;
    }

    public function defaultVersionDelayHours(): int
    {
        return max(0, (int) $this->get(self::KEY_DEFAULT_VERSION_DELAY_HOURS, self::DEFAULT_DELAY_HOURS));
    }

    /**
     * 사이트 기본 버전
     *
     * 자동 모드에서는 "등장한 지 N시간(기본 24시간) 지난 버전 중 가장 최신"을 사용한다.
     * 새 버전이 나오자마자 기본값이 되면 통계 표본이 거의 없어 화면이 비어 보이기 때문이다.
     */
    public function defaultVersion(): string
    {
        if ($this->defaultVersionMode() === self::MODE_MANUAL) {
            $manual = $this->get(self::KEY_DEFAULT_VERSION);

            if ($manual) {
                return (string) $manual;
            }
        }

        return $this->autoDefaultVersion();
    }

    /**
     * 자동 모드 기본 버전 계산
     */
    public function autoDefaultVersion(): string
    {
        return Cache::remember(self::AUTO_VERSION_CACHE_KEY, self::AUTO_VERSION_CACHE_TTL, function () {
            $delayHours = $this->defaultVersionDelayHours();

            $version = VersionHistory::query()
                ->where('start_date', '<=', now()->subHours($delayHours))
                ->orderByDesc('start_date')
                ->first();

            // 아직 대기 시간을 넘긴 버전이 없으면(사이트 초기 등) 가장 최신 버전을 그대로 쓴다.
            $version = $version ?: VersionHistory::query()->orderByDesc('start_date')->first();

            return $version ? $version->version_key : (string) config('erDev.defaultVersion');
        });
    }

    /**
     * 관리자 화면용 현재 설정 묶음
     */
    public function toArray(): array
    {
        return [
            'default_version_mode' => $this->defaultVersionMode(),
            'default_version' => $this->get(self::KEY_DEFAULT_VERSION),
            'default_version_delay_hours' => $this->defaultVersionDelayHours(),
            'default_tier' => $this->defaultTier(),
            'main_page_tier' => $this->mainPageTier(),
            // 참고용: 지금 실제로 적용되는 값
            'resolved_default_version' => $this->defaultVersion(),
            'auto_default_version' => $this->autoDefaultVersion(),
        ];
    }

    public function flushCache(): void
    {
        Cache::forget(Setting::CACHE_KEY);
        Cache::forget(self::AUTO_VERSION_CACHE_KEY);
    }
}
