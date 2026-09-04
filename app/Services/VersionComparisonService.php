<?php

namespace App\Services;

use App\Models\PatchNote;
use App\Models\VersionHistory;
use App\Traits\ErDevTrait;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 버전 간 전체 캐릭터 지표 변동 (관리자 전용)
 *
 * 메인 페이지의 패치 비교(PatchComparisonService)는 "패치노트에 직접 언급된 캐릭터"만 보여준다.
 * 여기서는 패치 여부와 무관하게 두 버전에 데이터가 있는 모든 캐릭터를 비교한다.
 * (직접 패치가 없었는데 메타가 움직인 캐릭터 = 간접 버프/너프를 찾기 위한 화면)
 *
 * 집계 테이블을 새로 만들지 않고 버전별 game_results_summary 두 개를 읽어 PHP 에서 맞춘다.
 * 티어 하나 기준이면 행이 수백 개 수준이라 그대로 다뤄도 부담이 없다.
 */
class VersionComparisonService
{
    use ErDevTrait;

    /** 집계 단위 */
    public const GROUP_BY = [
        'weapon' => '캐릭터+무기',
        'character' => '캐릭터 합산',
    ];

    /** 패치 여부 필터 */
    public const PATCH_FILTERS = [
        'all' => '전체',
        'patched' => '패치 있음',
        'unpatched' => '패치 없음 (간접 변동)',
        'buff' => '버프',
        'nerf' => '너프',
    ];

    /** 티어 점수(meta_score) 변동 방향 필터 */
    public const TREND_FILTERS = [
        'all' => '전체',
        'up' => '상승',
        'down' => '하락',
    ];

    /** 비교하는 지표 (합산 방식별로 나눠둔다) */
    private const SUM_METRICS = ['game_count', 'game_count_percent'];

    /** 게임수 가중평균으로 합치는 지표 */
    private const WEIGHTED_METRICS = [
        'meta_score',
        'top1_count_percent',
        'top4_count_percent',
        'avg_mmr_gain',
        'avg_team_kill_score',
    ];

    public function __construct(private PatchComparisonService $patchComparisonService)
    {
    }

    /**
     * 두 버전 비교
     *
     * @param array $filters version / base_version / min_tier / group_by / character_id
     *                       / weapon_type / patch_filter / search / min_game_count
     */
    public function compare(array $filters): array
    {
        // 관리자 전용이라 호출이 드물다. 요약 테이블 자체가 2시간 주기라 짧게 잡아도 충분하다.
        $cacheKey = 'version_comparison_' . md5(json_encode($filters));

        return Cache::remember($cacheKey, 600, fn () => $this->queryCompare($filters));
    }

    private function queryCompare(array $filters): array
    {
        $target = $this->resolveVersion($filters['version'] ?? null)
            ?? $this->patchComparisonService->getLatestVersion();

        if (!$target) {
            return $this->emptyResult(null, null, null, null);
        }

        $base = $this->resolveVersion($filters['base_version'] ?? null)
            ?? $this->patchComparisonService->getPreviousVersion($target);

        if (!$base) {
            return $this->emptyResult($target, null, null, null);
        }

        $targetTable = VersionedGameTableManager::getTableName('game_results_summary', $target->version_filters);
        $baseTable = VersionedGameTableManager::getTableName('game_results_summary', $base->version_filters);

        if (!Schema::hasTable($targetTable) || !Schema::hasTable($baseTable)) {
            return $this->emptyResult($target, $base, $targetTable, $baseTable);
        }

        $groupBy = isset($filters['group_by']) && isset(self::GROUP_BY[$filters['group_by']])
            ? $filters['group_by']
            : 'weapon';
        $minTier = $filters['min_tier'] ?? default_tier();

        $current = $this->groupRows($this->fetchRows($targetTable, $minTier, $filters), $groupBy);
        $previous = $this->groupRows($this->fetchRows($baseTable, $minTier, $filters), $groupBy);

        $patchNotes = $this->buildPatchNoteMap($target->id);
        $tagMap = $this->getWeaponTagMap();

        $rows = [];
        foreach ($current as $key => $cur) {
            $prev = $previous[$key] ?? null;
            $patch = $this->patchFor($patchNotes, $cur, $groupBy);

            $rows[] = [
                'key' => $key,
                'character_id' => $cur['character_id'],
                'character_name' => $cur['character_name'],
                'weapon_type' => $cur['weapon_type'],
                'tags' => $this->tagsFor($tagMap, $cur),
                'weapon_type_ko' => $cur['weapon_type'] ? $this->replaceWeaponType($cur['weapon_type'], 'ko') : '',
                // 아이콘 경로는 프론트(character.blade.php)와 동일한 규칙을 쓴다
                'character_image' => '/storage/Character/icon/' . str_pad((string) $cur['character_id'], 3, '0', STR_PAD_LEFT) . '.png',
                'current' => $this->presentMetrics($cur),
                'previous' => $prev ? $this->presentMetrics($prev) : null,
                'diff' => $prev ? $this->diffMetrics($cur, $prev) : null,
                // 이전 버전에 없던 조합 (신규 캐릭터 / 신규 무기 / 표본 부족)
                'is_new' => $prev === null,
                'patch_flag' => $patch['flag'],
                'patch_types' => $patch['types'],
                'patch_contents' => $patch['contents'],
            ];
        }

        // 이전 버전에만 있고 이번 버전에 사라진 조합 (표에는 넣지 않고 개수만 알린다)
        $droppedCount = count(array_diff_key($previous, $current));

        $rows = $this->applyFilters($rows, $filters);
        $rows = $this->sortRows($rows, $filters);

        return [
            'data' => $rows,
            'meta' => [
                'version' => $target->version_key,
                'base_version' => $base->version_key,
                'target_table' => $targetTable,
                'base_table' => $baseTable,
                'exists' => true,
                'group_by' => $groupBy,
                'min_tier' => $minTier,
                'count' => count($rows),
                'dropped_count' => $droppedCount,
                'weapon_types' => $this->availableWeaponTypes($targetTable, $minTier, $filters),
            ],
        ];
    }

    /**
     * 버전 키(12.2.0b)로 VersionHistory 찾기. 값이 없거나 없는 버전이면 null
     */
    private function resolveVersion(?string $version): ?VersionHistory
    {
        if (empty($version)) {
            return null;
        }

        $parts = parse_version_key($version);

        return VersionHistory::where('version_season', $parts['version_season'])
            ->where('version_major', $parts['version_major'])
            ->where('version_minor', $parts['version_minor'])
            ->when(
                $parts['version_hotfix'],
                fn ($q) => $q->where('version_hotfix', $parts['version_hotfix']),
                fn ($q) => $q->whereNull('version_hotfix')
            )
            ->first();
    }

    private function fetchRows(string $table, string $minTier, array $filters)
    {
        // 버전별 테이블은 만들어진 시점에 따라 컬럼 구성이 다르다.
        // (avg_team_kill_score 는 2025-10 에 추가돼서 그 이전 버전 테이블에는 없다)
        $columns = $this->getColumns($table);
        $select = [];
        foreach ([
            'character_id', 'character_name', 'weapon_type', 'meta_score', 'meta_tier',
            'game_count', 'game_count_percent', 'top1_count_percent', 'top4_count_percent',
            'avg_mmr_gain', 'avg_team_kill_score',
        ] as $column) {
            $select[] = in_array($column, $columns, true)
                ? $column
                : DB::raw("NULL as `{$column}`");
        }

        $query = DB::table($table)
            ->where('min_tier', $minTier)
            ->select($select);

        if (!empty($filters['character_id'])) {
            $query->where('character_id', $filters['character_id']);
        }

        // 'All' 은 알렉스처럼 무기 구분이 없는 캐릭터의 실제 값이기도 해서
        // TopRankStatService 와 동일하게 "전체" 의미로만 취급한다.
        if (!empty($filters['weapon_type']) && $filters['weapon_type'] !== 'All') {
            $query->where('weapon_type', $filters['weapon_type']);
        }

        return $query->get();
    }

    /**
     * 집계 단위로 묶기
     *
     * 캐릭터 합산 모드에서는 게임수 가중평균을 쓴다.
     * (승률/TOP4율/평균획득은 가중평균이 곧 전체 합산값과 같다. meta_score 만 근사치)
     */
    private function groupRows($rows, string $groupBy): array
    {
        $out = [];

        foreach ($rows as $row) {
            $key = $groupBy === 'character'
                ? (string) $row->character_id
                : $row->character_id . '_' . $row->weapon_type;

            if (!isset($out[$key])) {
                $out[$key] = [
                    'character_id' => (int) $row->character_id,
                    'character_name' => $row->character_name,
                    'weapon_type' => $groupBy === 'character' ? null : $row->weapon_type,
                    'meta_tier' => $groupBy === 'character' ? null : $row->meta_tier,
                    'weapon_types' => [],
                ];

                foreach (self::SUM_METRICS as $metric) {
                    $out[$key][$metric] = 0.0;
                }
                foreach (self::WEIGHTED_METRICS as $metric) {
                    $out[$key]['_w_' . $metric] = 0.0;
                }
            }

            $weight = (float) $row->game_count;
            $out[$key]['weapon_types'][] = $row->weapon_type;

            foreach (self::SUM_METRICS as $metric) {
                $out[$key][$metric] += (float) ($row->{$metric} ?? 0);
            }
            foreach (self::WEIGHTED_METRICS as $metric) {
                $out[$key]['_w_' . $metric] += (float) ($row->{$metric} ?? 0) * $weight;
            }
        }

        // 가중합을 평균으로 환산
        foreach ($out as $key => $group) {
            $weight = $group['game_count'];
            foreach (self::WEIGHTED_METRICS as $metric) {
                $out[$key][$metric] = $weight > 0 ? $group['_w_' . $metric] / $weight : 0.0;
                unset($out[$key]['_w_' . $metric]);
            }
        }

        return $out;
    }

    /**
     * 화면에 내려줄 지표 묶음
     */
    private function presentMetrics(array $group): array
    {
        return [
            'game_count' => (int) $group['game_count'],
            'game_count_percent' => round($group['game_count_percent'], 3),
            'meta_score' => round($group['meta_score'], 2),
            'meta_tier' => $group['meta_tier'],
            'top1_count_percent' => round($group['top1_count_percent'], 2),
            'top4_count_percent' => round($group['top4_count_percent'], 2),
            'avg_mmr_gain' => round($group['avg_mmr_gain'], 2),
            'avg_team_kill_score' => round($group['avg_team_kill_score'], 2),
        ];
    }

    private function diffMetrics(array $current, array $previous): array
    {
        $diff = [];

        foreach (array_merge(self::SUM_METRICS, self::WEIGHTED_METRICS) as $metric) {
            $diff[$metric] = round($current[$metric] - $previous[$metric], $metric === 'game_count' ? 0 : 2);
        }

        return $diff;
    }

    /**
     * 대상 버전의 캐릭터 패치노트를 전체/무기별로 나눠 담아둔다
     */
    private function buildPatchNoteMap(int $versionHistoryId): array
    {
        $notes = PatchNote::where('version_history_id', $versionHistoryId)
            ->where('category', '캐릭터')
            ->whereNotNull('target_id')
            ->get();

        $map = ['byCharacter' => [], 'global' => [], 'specific' => []];

        foreach ($notes as $note) {
            $characterId = (int) $note->target_id;
            $map['byCharacter'][$characterId][] = $note;

            if (empty($note->weapon_type)) {
                $map['global'][$characterId][] = $note;
            } else {
                $weaponTypeEn = $this->replaceWeaponType($note->weapon_type, 'en');
                $map['specific'][$characterId . '_' . $weaponTypeEn][] = $note;
            }
        }

        return $map;
    }

    /**
     * 행에 해당하는 패치노트 요약
     */
    private function patchFor(array $map, array $group, string $groupBy): array
    {
        $characterId = $group['character_id'];

        if ($groupBy === 'character') {
            $notes = $map['byCharacter'][$characterId] ?? [];
        } else {
            $notes = array_merge(
                $map['global'][$characterId] ?? [],
                $map['specific'][$characterId . '_' . $group['weapon_type']] ?? []
            );
        }

        if (empty($notes)) {
            return ['flag' => null, 'types' => [], 'contents' => []];
        }

        $types = array_values(array_unique(array_map(fn ($n) => $n->patch_type, $notes)));
        $hasBuff = in_array('버프', $types, true);
        $hasNerf = in_array('너프', $types, true);

        $flag = match (true) {
            $hasBuff && $hasNerf => 'mixed',
            $hasBuff => 'buff',
            $hasNerf => 'nerf',
            default => 'other',
        };

        return [
            'flag' => $flag,
            'types' => $types,
            'contents' => array_values(array_map(
                fn ($n) => trim(($n->weapon_type ? "[{$n->weapon_type}] " : '') . $n->content),
                $notes
            )),
        ];
    }

    /**
     * 캐릭터+무기 단위 태그 맵 (캐릭터 통계 페이지와 동일한 기준)
     */
    private function getWeaponTagMap(): array
    {
        $rows = DB::table('character_weapon_character_tag as cwct')
            ->join('character_tags as ct', 'ct.id', '=', 'cwct.character_tag_id')
            ->select('cwct.character_id', 'cwct.weapon_type', 'ct.name as tag_name')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[$row->character_id . '_' . $row->weapon_type][] = $row->tag_name;
        }

        return $map;
    }

    /**
     * 행에 해당하는 태그 목록
     * 캐릭터 합산 모드에서는 그 캐릭터가 가진 모든 무기의 태그를 합친다.
     */
    private function tagsFor(array $tagMap, array $group): array
    {
        $tags = [];

        foreach ($group['weapon_types'] as $weaponType) {
            foreach ($tagMap[$group['character_id'] . '_' . $weaponType] ?? [] as $tag) {
                $tags[$tag] = true;
            }
        }

        return array_keys($tags);
    }

    /**
     * 표본/패치 여부/태그/추세/이름 필터
     */
    private function applyFilters(array $rows, array $filters): array
    {
        $minGameCount = (int) ($filters['min_game_count'] ?? 0);
        $patchFilter = $filters['patch_filter'] ?? 'all';
        $trend = $filters['trend'] ?? 'all';
        $tag = trim((string) ($filters['tag'] ?? ''));
        $search = trim((string) ($filters['search'] ?? ''));

        return array_values(array_filter($rows, function ($row) use ($minGameCount, $patchFilter, $trend, $tag, $search) {
            if ($minGameCount > 0 && $row['current']['game_count'] < $minGameCount) {
                return false;
            }

            if ($search !== '' && mb_stripos($row['character_name'], $search) === false) {
                return false;
            }

            if ($tag !== '' && !in_array($tag, $row['tags'], true)) {
                return false;
            }

            // 티어 점수(meta_score) 기준 상승/하락. 이전 버전 값이 없으면 판단할 수 없어 제외한다.
            if ($trend === 'up' || $trend === 'down') {
                $metaDiff = $row['diff']['meta_score'] ?? null;
                if ($metaDiff === null || ($trend === 'up' ? $metaDiff <= 0 : $metaDiff >= 0)) {
                    return false;
                }
            }

            return match ($patchFilter) {
                'patched' => $row['patch_flag'] !== null,
                'unpatched' => $row['patch_flag'] === null,
                'buff' => in_array($row['patch_flag'], ['buff', 'mixed'], true),
                'nerf' => in_array($row['patch_flag'], ['nerf', 'mixed'], true),
                default => true,
            };
        }));
    }

    /**
     * 변동폭 기준 정렬 (이전 버전이 없는 행은 항상 뒤로)
     */
    private function sortRows(array $rows, array $filters): array
    {
        $sort = $filters['sort'] ?? 'meta_score';
        $allowed = array_merge(self::SUM_METRICS, self::WEIGHTED_METRICS);
        if (!in_array($sort, $allowed, true)) {
            $sort = 'meta_score';
        }
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 1 : -1;

        usort($rows, function ($a, $b) use ($sort, $direction) {
            $aDiff = $a['diff'][$sort] ?? null;
            $bDiff = $b['diff'][$sort] ?? null;

            if ($aDiff === null || $bDiff === null) {
                return ($aDiff === null ? 1 : 0) - ($bDiff === null ? 1 : 0);
            }

            return ($aDiff <=> $bDiff) * $direction;
        });

        return $rows;
    }

    /**
     * 현재 조건에서 실제로 존재하는 무기군만 선택지로 준다
     */
    private function availableWeaponTypes(string $table, string $minTier, array $filters): array
    {
        $query = DB::table($table)->where('min_tier', $minTier);

        if (!empty($filters['character_id'])) {
            $query->where('character_id', $filters['character_id']);
        }

        return $query->distinct()->orderBy('weapon_type')->pluck('weapon_type')
            ->filter()
            ->map(fn ($code) => ['value' => $code, 'label' => $this->replaceWeaponType($code, 'ko')])
            ->values()->all();
    }

    private function emptyResult(?VersionHistory $target, ?VersionHistory $base, ?string $targetTable, ?string $baseTable): array
    {
        return [
            'data' => [],
            'meta' => [
                'version' => $target?->version_key,
                'base_version' => $base?->version_key,
                'target_table' => $targetTable,
                'base_table' => $baseTable,
                'exists' => false,
                'count' => 0,
                'dropped_count' => 0,
                'weapon_types' => [],
            ],
        ];
    }

    /**
     * 화면 필터용 선택지
     */
    public function getFilterOptions(): array
    {
        $versions = VersionHistory::query()
            ->orderByDesc('start_date')
            ->limit(30)
            ->get()
            ->map(fn ($v) => ['value' => $v->version_key, 'label' => $v->version_key])
            ->all();

        return [
            'versions' => $versions,
            'tiers' => ['All', 'Platinum', 'Diamond', 'Diamond2', 'Meteorite', 'Mithrillow', 'Mithrilhigh', 'Top'],
            'default_tier' => default_tier(),
            'characters' => DB::table('characters')->orderBy('name')->get(['id', 'name'])
                ->map(fn ($c) => ['value' => (string) $c->id, 'label' => $c->name])->all(),
            'group_by' => collect(self::GROUP_BY)->map(fn ($label, $value) => compact('value', 'label'))->values()->all(),
            'patch_filters' => collect(self::PATCH_FILTERS)->map(fn ($label, $value) => compact('value', 'label'))->values()->all(),
            'trend_filters' => collect(self::TREND_FILTERS)->map(fn ($label, $value) => compact('value', 'label'))->values()->all(),
            'tags' => DB::table('character_tags')->orderBy('name')->pluck('name')
                ->map(fn ($name) => ['value' => $name, 'label' => $name])->all(),
        ];
    }
}
