<?php

namespace App\Services;

use App\Models\GameTrait;
use App\Models\TraitVersionGroup;
use App\Models\VersionHistory;
use Illuminate\Support\Facades\Cache;

/**
 * 특성 그룹(메인/서브1/서브2) 조회 서비스
 *
 * 그룹은 공식 API에 없어 관리자에서 수기로 지정하며, 패치마다 바뀔 수 있다.
 * traits.trait_group 은 "현재" 값이고, 변경 이력은 trait_version_groups 에 쌓인다.
 * 특정 버전의 그룹은 "해당 버전 이하에서 가장 최근 기록"을 사용하고,
 * 기록이 없으면 traits.trait_group 으로 폴백한다.
 */
class TraitGroupService
{
    public const GROUPS = ['main', 'sub1', 'sub2'];

    public const GROUP_LABELS = [
        'main' => '메인',
        'sub1' => '서브1',
        'sub2' => '서브2',
    ];

    /** 정렬 순서: 메인 → 서브1 → 서브2 */
    public const GROUP_ORDER = [
        'main' => 0,
        'sub1' => 1,
        'sub2' => 2,
    ];

    private const CACHE_PREFIX = 'trait_group_map_';
    private const CACHE_KEYS_KEY = 'trait_group_map_keys';

    /**
     * 해당 버전 기준 [trait_id => trait_group] 맵 반환
     *
     * @param array $versionFilters version_season / version_major / version_minor
     */
    public function getGroupMap(array $versionFilters = []): array
    {
        $season = $versionFilters['version_season'] ?? null;
        $major = $versionFilters['version_major'] ?? null;
        $minor = $versionFilters['version_minor'] ?? null;

        $cacheKey = self::CACHE_PREFIX . "{$season}_{$major}_{$minor}";

        return Cache::remember($cacheKey, 3600, function () use ($cacheKey, $season, $major, $minor) {
            $this->rememberCacheKey($cacheKey);

            // 1. 마스터의 현재 그룹을 기본값으로 깔아둔다.
            $map = GameTrait::query()
                ->whereNotNull('trait_group')
                ->pluck('trait_group', 'id')
                ->all();

            // 2. 버전이 지정되지 않았으면 현재 그룹이 곧 정답
            if ($season === null || $major === null || $minor === null) {
                return $map;
            }

            // 3. 대상 버전 이하의 변경 이력을 오래된 순으로 덮어쓴다.
            //    (같은 특성에 여러 기록이 있으면 가장 최근 것이 남는다)
            $history = TraitVersionGroup::query()
                ->where(function ($query) use ($season, $major, $minor) {
                    $query->where('version_season', '<', $season)
                        ->orWhere(function ($q) use ($season, $major, $minor) {
                            $q->where('version_season', $season)
                                ->where(function ($q2) use ($major, $minor) {
                                    $q2->where('version_major', '<', $major)
                                        ->orWhere(function ($q3) use ($major, $minor) {
                                            $q3->where('version_major', $major)
                                                ->where('version_minor', '<=', $minor);
                                        });
                                });
                        });
                })
                ->orderBy('version_season')
                ->orderBy('version_major')
                ->orderBy('version_minor')
                ->get(['trait_id', 'trait_group']);

            foreach ($history as $row) {
                $map[$row->trait_id] = $row->trait_group;
            }

            return $map;
        });
    }

    /**
     * 특성 그룹 변경을 현재 진행중인 버전 기준으로 이력에 남긴다.
     * 관리자에서 그룹을 지정할 때 호출한다.
     */
    public function recordChange(int $traitId, ?string $group, ?VersionHistory $version = null): void
    {
        if (!$group || !in_array($group, self::GROUPS, true)) {
            return;
        }

        $version = $version ?: VersionHistory::active()
            ->orderBy('version_season', 'desc')
            ->orderBy('version_major', 'desc')
            ->orderBy('version_minor', 'desc')
            ->first();

        if (!$version) {
            return;
        }

        TraitVersionGroup::updateOrCreate(
            [
                'version_season' => $version->version_season,
                'version_major' => $version->version_major,
                'version_minor' => $version->version_minor,
                'trait_id' => $traitId,
            ],
            ['trait_group' => $group]
        );

        $this->flushCache();
    }

    /**
     * 그룹 정렬 순서 반환 (알 수 없으면 맨 뒤)
     */
    public static function groupOrder(?string $group): int
    {
        return self::GROUP_ORDER[$group] ?? 99;
    }

    /**
     * 그룹 한글 라벨 반환
     */
    public static function groupLabel(?string $group): string
    {
        return self::GROUP_LABELS[$group] ?? '미지정';
    }

    public function flushCache(): void
    {
        foreach ((array) Cache::get(self::CACHE_KEYS_KEY, []) as $key) {
            Cache::forget($key);
        }

        Cache::forget(self::CACHE_KEYS_KEY);
    }

    /**
     * 버전별로 키가 갈리므로 무효화를 위해 발급한 키를 따로 모아둔다.
     */
    private function rememberCacheKey(string $cacheKey): void
    {
        $keys = (array) Cache::get(self::CACHE_KEYS_KEY, []);

        if (!in_array($cacheKey, $keys, true)) {
            $keys[] = $cacheKey;
            Cache::put(self::CACHE_KEYS_KEY, $keys, 86400);
        }
    }
}
