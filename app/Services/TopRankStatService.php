<?php

namespace App\Services;

use App\Models\VersionHistory;
use App\Traits\ErDevTrait;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * TOP4 상세 지표 조회 (관리자 전용)
 *
 * 기존 버전별 요약 테이블은 순위(game_rank 1~8)별로 행이 나뉘어 있다.
 * 여기서는 그 행들을 항목 단위(캐릭터를 고르면 항목 x 무기)로 합치되,
 * TOP4(1~4위)와 전체(1~8위)를 한 번의 조회로 나란히 계산한다.
 * 항목 단위일 때는 사용률 순 주 사용 캐릭터를 함께 붙인다.
 *
 * 별도 집계 테이블을 만들지 않으므로 스케줄러에 영향이 없다.
 */
class TopRankStatService
{
    use ErDevTrait;

    /**
     * 지원하는 통계 종류
     * image_dir 은 public/storage 아래 아이콘 디렉토리명이다.
     */
    public const TYPES = [
        'trait' => [
            'label' => '특성',
            'table' => 'game_results_trait_summary',
            'id_column' => 'trait_id',
            'name_table' => 'traits',
            'image_dir' => 'Trait',
        ],
        'equipment' => [
            'label' => '아이템',
            'table' => 'game_results_equipment_summary',
            'id_column' => 'equipment_id',
            'name_table' => 'equipments',
            'image_dir' => 'Equipment',
        ],
        'tactical_skill' => [
            'label' => '전술스킬',
            'table' => 'game_results_tactical_skill_summary',
            'id_column' => 'tactical_skill_id',
            'name_table' => 'tactical_skills',
            'image_dir' => 'TacticalSkill',
        ],
    ];

    /** 특성 그룹 표기 */
    private const TRAIT_GROUP_LABELS = [
        'main' => '메인',
        'sub1' => '서브1',
        'sub2' => '서브2',
    ];

    /** TOP4 기준 순위 */
    private const TOP_RANK = 4;

    /** 항목마다 내려보내는 주 사용 캐릭터 수 (화면은 3명만 먼저 보여주고 펼치면 전부) */
    private const TOP_CHARACTER_LIMIT = 10;

    /**
     * 지표 조회
     *
     * @param array $filters type / version / min_tier / character_id / weapon_type / search / min_game_count
     */
    public function getStats(array $filters): array
    {
        // 관리자 전용이라 호출 빈도가 낮다. 콜드 상태에서 1초 가까이 걸리는 것만 캐시로 눌러준다.
        // (요약 테이블 자체가 2시간 주기로 갱신되므로 짧게 잡아도 충분하다)
        $cacheKey = 'top_rank_stat_' . md5(json_encode($filters));

        return Cache::remember($cacheKey, 600, fn () => $this->queryStats($filters));
    }

    private function queryStats(array $filters): array
    {
        $type = $filters['type'] ?? 'trait';
        $config = self::TYPES[$type] ?? self::TYPES['trait'];

        $versionParts = parse_version_key($filters['version'] ?? null);
        $tableName = VersionedGameTableManager::getTableName($config['table'], $versionParts);

        if (!Schema::hasTable($tableName)) {
            return ['data' => [], 'table' => $tableName, 'exists' => false];
        }

        $idColumn = $config['id_column'];
        $top = self::TOP_RANK;

        // 종류별 그룹 기준
        //  - 아이템: 캐릭터 상세와 동일하게 무기는 'Weapon', 나머지는 부위(item_type2)
        //  - 특성  : 그룹(main/sub1/sub2). 마스터 값을 쓰고 버전별 이력은 아래에서 덮어쓴다
        //  - 전술스킬: 그룹 구분 없음
        $groupSelect = match ($type) {
            'equipment' => "CASE WHEN n.item_type1 = 'Weapon' THEN 'Weapon' ELSE n.item_type2 END",
            'trait' => 'n.trait_group',
            default => "''",
        };

        // 2차 분류 - 아이템은 등급, 특성은 카테고리(파괴/혼돈/지원/저항)
        $subGroupSelect = match ($type) {
            'equipment' => 'n.item_grade',
            'trait' => 'n.category',
            default => "''",
        };

        // 캐릭터를 고르지 않으면 항목 단위로 전 캐릭터를 합친 평균 지표를 보여준다.
        // 캐릭터를 고르면 그 캐릭터의 무기별로 나눈다. (무기군만 고르면 그 무기군 전체 합산)
        $perCharacter = !empty($filters['character_id']);

        $groupBy = ['s.' . $idColumn, 'n.name', DB::raw($groupSelect), DB::raw($subGroupSelect)];
        if ($perCharacter) {
            array_push($groupBy, 's.character_id', 's.weapon_type', 'c.name');
        }

        $query = DB::table($tableName . ' as s')
            ->join('characters as c', 'c.id', '=', 's.character_id')
            ->leftJoin($config['name_table'] . ' as n', 'n.id', '=', 's.' . $idColumn)
            ->where('s.min_tier', $filters['min_tier'] ?? 'Diamond')
            ->groupBy($groupBy);

        $query->select([
            DB::raw("s.`{$idColumn}` as item_id"),
            DB::raw('n.name as item_name'),
            DB::raw($groupSelect . ' as group_code'),
            DB::raw($subGroupSelect . ' as sub_group_code'),
            DB::raw($perCharacter ? 's.character_id' : 'NULL as character_id'),
            DB::raw($perCharacter ? 'c.name as character_name' : 'NULL as character_name'),
            DB::raw($perCharacter ? 's.weapon_type' : 'NULL as weapon_type'),

            ...self::metricSelects('s'),
        ]);

        $this->applyFilters($query, $filters, $groupSelect, $subGroupSelect);

        $minGameCount = (int) ($filters['min_game_count'] ?? 0);
        if ($minGameCount > 0) {
            $query->havingRaw("SUM(CASE WHEN s.game_rank <= {$top} THEN s.game_rank_count ELSE 0 END) >= ?", [$minGameCount]);
        }

        $rows = $query->orderByRaw("SUM(CASE WHEN s.game_rank <= {$top} THEN s.game_rank_count ELSE 0 END) DESC")
            ->limit(500)
            ->get();

        // 전 캐릭터 합산일 때만 "주 사용 캐릭터"를 붙인다 (캐릭터를 고르면 그 캐릭터만 나오므로 의미가 없다)
        $topCharacters = (!$perCharacter && $rows->isNotEmpty())
            ? $this->topCharacters($tableName, $versionParts, $idColumn, $config['name_table'], $filters,
                $groupSelect, $subGroupSelect, $rows->pluck('item_id')->all())
            : [];

        return [
            'data' => $rows->map(fn ($row) => [
                ...$this->formatRow($row, $type, $config),
                'top_characters' => $topCharacters[$row->item_id] ?? [],
            ])->all(),
            'table' => $tableName,
            'exists' => true,
            'per_character' => $perCharacter,
            // 무기군은 캐릭터마다 다르고(알렉스는 합계 'All', 에키온은 전용 3종)
            // 버전에 따라서도 달라지므로, 지금 조건에서 실제로 존재하는 값만 내려준다.
            'weapon_types' => $this->availableWeaponTypes($tableName, $filters),
        ];
    }

    private function applyFilters($query, array $filters, string $groupSelect, string $subGroupSelect): void
    {
        if (!empty($filters['character_id'])) {
            $query->where('s.character_id', $filters['character_id']);
        }

        if (!empty($filters['weapon_type']) && $filters['weapon_type'] !== 'All') {
            $query->where('s.weapon_type', $filters['weapon_type']);
        }

        if (!empty($filters['group']) && $filters['group'] !== 'All') {
            $query->whereRaw("{$groupSelect} = ?", [$filters['group']]);
        }

        if (!empty($filters['sub_group']) && $filters['sub_group'] !== 'All') {
            $query->whereRaw("{$subGroupSelect} = ?", [$filters['sub_group']]);
        }

        if (!empty($filters['search'])) {
            $query->where('n.name', 'like', '%' . $filters['search'] . '%');
        }
    }

    /**
     * 항목별 주 사용 캐릭터·무기 (사용률 순)
     *
     * 단순 게임수로 정렬하면 픽률 높은 캐릭터만 올라와서, 실제로 "이 항목을 주로 쓰는" 캐릭터가 안 보인다.
     *   사용률 = (그 캐릭터·무기가 이 항목을 쓴 게임수) / (그 캐릭터·무기의 전체 게임수)
     * 분모는 같은 버전·티어의 game_results_rank_summary 합계다.
     * 아이템(부위별 최종 장비)·특성·전술스킬 모두 한 게임에 같은 항목은 1번만 잡히므로 100% 를 넘지 않는다.
     *
     * @return array<int, array> [item_id => [ {character_id, character_name, weapon_type, weapon_label,
     *                                          game_count, total_count, usage_rate, avg_mmr_gain, top_rate}, ... ]]
     */
    private function topCharacters(
        string $tableName,
        array $versionParts,
        string $idColumn,
        string $nameTable,
        array $filters,
        string $groupSelect,
        string $subGroupSelect,
        array $itemIds
    ): array {
        $minTier = $filters['min_tier'] ?? 'Diamond';
        $top = self::TOP_RANK;

        $query = DB::table($tableName . ' as s')
            ->leftJoin($nameTable . ' as n', 'n.id', '=', 's.' . $idColumn)
            ->where('s.min_tier', $minTier)
            ->whereIn('s.' . $idColumn, $itemIds)
            ->groupBy('s.' . $idColumn, 's.character_id', 's.weapon_type')
            ->select([
                DB::raw("s.`{$idColumn}` as item_id"),
                's.character_id',
                's.weapon_type',
                DB::raw('SUM(s.game_rank_count) as game_count'),
                DB::raw("SUM(CASE WHEN s.game_rank <= {$top} THEN s.game_rank_count ELSE 0 END) as top_count"),
                DB::raw('SUM(s.avg_mmr_gain * s.game_rank_count) / NULLIF(SUM(s.game_rank_count), 0) as avg_mmr_gain'),
            ]);
        $this->applyFilters($query, $filters, $groupSelect, $subGroupSelect);

        // 표본이 너무 적은 캐릭터는 사용률이 튄다 (2게임 중 2게임 = 100%)
        $minCharacterGames = max(1, (int) ($filters['min_character_game_count'] ?? 10));
        $query->havingRaw('SUM(s.game_rank_count) >= ?', [$minCharacterGames]);

        $usages = $query->get();

        // 분모: 캐릭터·무기별 전체 게임수
        $rankTableName = VersionedGameTableManager::getTableName('game_results_rank_summary', $versionParts);
        if (!Schema::hasTable($rankTableName)) {
            return [];
        }

        $totals = DB::table($rankTableName)
            ->where('min_tier', $minTier)
            ->groupBy('character_id', 'weapon_type')
            ->select('character_id', 'weapon_type', DB::raw('SUM(game_rank_count) as total'))
            ->get()
            ->mapWithKeys(fn ($r) => [$r->character_id . '|' . $r->weapon_type => (int) $r->total])
            ->all();

        $characterNames = DB::table('characters')->pluck('name', 'id')->all();

        $result = [];
        foreach ($usages as $u) {
            $total = $totals[$u->character_id . '|' . $u->weapon_type] ?? 0;
            if ($total <= 0) {
                continue;
            }
            $gameCount = (int) $u->game_count;

            $result[$u->item_id][] = [
                'character_id' => $u->character_id,
                'character_name' => $characterNames[$u->character_id] ?? ('#' . $u->character_id),
                'weapon_type' => $u->weapon_type,
                'weapon_label' => $this->replaceWeaponType($u->weapon_type, 'ko'),
                'game_count' => $gameCount,
                'total_count' => $total,
                'usage_rate' => round(min($gameCount / $total, 1) * 100, 2),
                'avg_mmr_gain' => round((float) ($u->avg_mmr_gain ?? 0), 1),
                'top_rate' => $gameCount ? round((int) $u->top_count / $gameCount * 100, 2) : 0,
            ];
        }

        foreach ($result as $itemId => $list) {
            usort($list, fn ($a, $b) => [$b['usage_rate'], $b['game_count']] <=> [$a['usage_rate'], $a['game_count']]);
            $result[$itemId] = array_slice($list, 0, self::TOP_CHARACTER_LIMIT);
        }

        return $result;
    }

    /**
     * 비율 계산 등 화면에서 바로 쓸 수 있는 형태로 정리
     */
    private function formatRow(object $row, string $type, array $config): array
    {
        return [
            'item_id' => $row->item_id,
            'item_name' => $row->item_name ?? ('#' . $row->item_id),
            'image' => "/storage/{$config['image_dir']}/{$row->item_id}.png",
            'group_code' => $row->group_code ?? '',
            'group_label' => $this->groupLabel($type, $row->group_code ?? ''),
            'sub_group_code' => $row->sub_group_code ?? '',
            'sub_group_label' => $this->subGroupLabel($type, $row->sub_group_code ?? ''),
            'character_id' => $row->character_id,
            'character_name' => $row->character_name,
            'weapon_type' => $row->weapon_type,

            ...self::formatMetrics($row),
        ];
    }

    /**
     * TOP4(1~4위) / 전체(1~8위) 지표를 한 번에 계산하는 SELECT 절
     * 요약 테이블(순위별 행)을 GROUP BY 로 접을 때 쓴다.
     */
    private static function metricSelects(string $alias): array
    {
        $s = $alias;
        $top = self::TOP_RANK;

        return [
            // 전체(1~8위)
            DB::raw("SUM({$s}.game_rank_count) as all_game_count"),
            DB::raw("SUM({$s}.avg_mmr_gain * {$s}.game_rank_count) / NULLIF(SUM({$s}.game_rank_count), 0) as all_avg_mmr_gain"),
            DB::raw("SUM({$s}.avg_team_kill_score * {$s}.game_rank_count) / NULLIF(SUM({$s}.game_rank_count), 0) as all_avg_team_kill"),
            DB::raw("SUM({$s}.positive_count) as all_positive_count"),
            DB::raw("SUM({$s}.positive_avg_mmr_gain * {$s}.positive_count) / NULLIF(SUM({$s}.positive_count), 0) as all_positive_avg"),
            DB::raw("SUM({$s}.negative_count) as all_negative_count"),
            DB::raw("SUM({$s}.negative_avg_mmr_gain * {$s}.negative_count) / NULLIF(SUM({$s}.negative_count), 0) as all_negative_avg"),

            // TOP4(1~4위)
            DB::raw("SUM(CASE WHEN {$s}.game_rank <= {$top} THEN {$s}.game_rank_count ELSE 0 END) as top_game_count"),
            DB::raw("SUM(CASE WHEN {$s}.game_rank <= {$top} THEN {$s}.avg_mmr_gain * {$s}.game_rank_count ELSE 0 END)
                     / NULLIF(SUM(CASE WHEN {$s}.game_rank <= {$top} THEN {$s}.game_rank_count ELSE 0 END), 0) as top_avg_mmr_gain"),
            DB::raw("SUM(CASE WHEN {$s}.game_rank <= {$top} THEN {$s}.avg_team_kill_score * {$s}.game_rank_count ELSE 0 END)
                     / NULLIF(SUM(CASE WHEN {$s}.game_rank <= {$top} THEN {$s}.game_rank_count ELSE 0 END), 0) as top_avg_team_kill"),
            DB::raw("SUM(CASE WHEN {$s}.game_rank <= {$top} THEN {$s}.positive_count ELSE 0 END) as top_positive_count"),
            DB::raw("SUM(CASE WHEN {$s}.game_rank <= {$top} THEN {$s}.positive_avg_mmr_gain * {$s}.positive_count ELSE 0 END)
                     / NULLIF(SUM(CASE WHEN {$s}.game_rank <= {$top} THEN {$s}.positive_count ELSE 0 END), 0) as top_positive_avg"),
            DB::raw("SUM(CASE WHEN {$s}.game_rank <= {$top} THEN {$s}.negative_count ELSE 0 END) as top_negative_count"),
            DB::raw("SUM(CASE WHEN {$s}.game_rank <= {$top} THEN {$s}.negative_avg_mmr_gain * {$s}.negative_count ELSE 0 END)
                     / NULLIF(SUM(CASE WHEN {$s}.game_rank <= {$top} THEN {$s}.negative_count ELSE 0 END), 0) as top_negative_avg"),
        ];
    }

    /**
     * metricSelects() 결과 행을 화면용 top / all / top_rate 구조로 정리
     */
    private static function formatMetrics(object $row): array
    {
        $allCount = (int) $row->all_game_count;
        $topCount = (int) $row->top_game_count;
        $round = fn ($value, int $precision = 1) => round((float) ($value ?? 0), $precision);

        return [
            'top' => [
                'game_count' => $topCount,
                'avg_mmr_gain' => $round($row->top_avg_mmr_gain),
                'avg_team_kill' => $round($row->top_avg_team_kill, 2),
                'positive_percent' => $topCount ? round((int) $row->top_positive_count / $topCount * 100, 2) : 0,
                'positive_avg' => $round($row->top_positive_avg),
                'negative_percent' => $topCount ? round((int) $row->top_negative_count / $topCount * 100, 2) : 0,
                'negative_avg' => $round($row->top_negative_avg),
            ],
            'all' => [
                'game_count' => $allCount,
                'avg_mmr_gain' => $round($row->all_avg_mmr_gain),
                'avg_team_kill' => $round($row->all_avg_team_kill, 2),
                'positive_percent' => $allCount ? round((int) $row->all_positive_count / $allCount * 100, 2) : 0,
                'positive_avg' => $round($row->all_positive_avg),
                'negative_percent' => $allCount ? round((int) $row->all_negative_count / $allCount * 100, 2) : 0,
                'negative_avg' => $round($row->all_negative_avg),
            ],
            // TOP4 진입 비율 (참고용)
            'top_rate' => $allCount ? round($topCount / $allCount * 100, 2) : 0,
        ];
    }

    /**
     * 현재 조건(버전/티어/캐릭터)에서 선택 가능한 무기군 목록
     */
    private function availableWeaponTypes(string $tableName, array $filters): array
    {
        $query = DB::table($tableName)
            ->where('min_tier', $filters['min_tier'] ?? 'Diamond');

        if (!empty($filters['character_id'])) {
            $query->where('character_id', $filters['character_id']);
        }

        return $query->distinct()->orderBy('weapon_type')->pluck('weapon_type')
            ->filter()
            ->map(fn ($code) => ['value' => $code, 'label' => $this->replaceWeaponType($code, 'ko')])
            ->values()->all();
    }

    /**
     * 그룹 코드를 한글 라벨로
     */
    private function groupLabel(string $type, ?string $code): string
    {
        if (!$code) {
            return '';
        }

        return match ($type) {
            'trait' => self::TRAIT_GROUP_LABELS[$code] ?? $code,
            // 아이템은 캐릭터 상세와 동일한 표기를 쓴다 (무기 / 머리 / 옷 ...)
            'equipment' => $this->replaceItemType2($code, 'ko'),
            default => $code,
        };
    }

    /**
     * 2차 분류 코드를 한글 라벨로 (아이템 등급 / 특성 카테고리)
     */
    private function subGroupLabel(string $type, ?string $code): string
    {
        if (!$code) {
            return '';
        }

        return match ($type) {
            'equipment' => $this->replaceItemGrade($code, 'ko'),
            // 특성 카테고리는 이미 한글이라 그대로 쓴다
            default => $code,
        };
    }

    /**
     * 종류별 필터 라벨 (화면에 "그룹" 대신 실제 이름을 보여준다)
     */
    public function getFilterLabels(): array
    {
        return [
            'trait' => ['group' => '메인/서브', 'sub_group' => '카테고리'],
            'equipment' => ['group' => '부위', 'sub_group' => '등급'],
            'tactical_skill' => ['group' => '', 'sub_group' => ''],
        ];
    }

    /**
     * 종류별 2차 분류 선택지
     */
    public function getSubGroupOptions(string $type): array
    {
        return match ($type) {
            // 등급은 낮은 것부터 순서를 고정한다 (DISTINCT 정렬로는 뒤섞인다)
            'equipment' => collect(['Common', 'Uncommon', 'Rare', 'Epic', 'Legend', 'Mythic'])
                ->map(fn ($code) => ['value' => $code, 'label' => $this->replaceItemGrade($code, 'ko')])
                ->all(),
            'trait' => DB::table('traits')->whereNotNull('category')->distinct()
                ->orderBy('category')->pluck('category')
                ->map(fn ($code) => ['value' => $code, 'label' => $code])
                ->values()->all(),
            default => [],
        };
    }

    /**
     * 종류별 그룹 선택지 (화면 필터용)
     */
    public function getGroupOptions(string $type): array
    {
        $options = match ($type) {
            'trait' => collect(self::TRAIT_GROUP_LABELS)
                ->map(fn ($label, $code) => ['value' => $code, 'label' => $label])
                ->values()->all(),
            'equipment' => DB::table('equipments')
                ->selectRaw("DISTINCT CASE WHEN item_type1 = 'Weapon' THEN 'Weapon' ELSE item_type2 END as code")
                ->whereNotNull('item_type2')
                ->orderBy('code')
                ->pluck('code')
                ->filter()
                ->map(fn ($code) => ['value' => $code, 'label' => $this->replaceItemType2($code, 'ko')])
                ->values()->all(),
            default => [],
        };

        return $options;
    }

    /**
     * 화면 필터용 선택지
     */
    public function getFilterOptions(): array
    {
        return [
            'types' => collect(self::TYPES)->map(fn ($c, $key) => ['value' => $key, 'label' => $c['label']])->values()->all(),
            'versions' => VersionHistory::query()
                ->orderByDesc('start_date')
                ->limit(10)
                ->get()
                ->map(fn ($v) => ['value' => $v->version_key, 'label' => $v->version_key])
                ->all(),
            'tiers' => ['All', 'Platinum', 'Diamond', 'Diamond2', 'Meteorite', 'Mithrillow', 'Mithrilhigh', 'Top'],
            'characters' => DB::table('characters')->orderBy('name')->get(['id', 'name'])
                ->map(fn ($c) => ['value' => (string) $c->id, 'label' => $c->name])->all(),
            // 종류별 그룹 선택지 (특성: 메인/서브1/서브2, 아이템: 무기/부위)
            'groups' => collect(self::TYPES)->keys()
                ->mapWithKeys(fn ($type) => [$type => $this->getGroupOptions($type)])->all(),
            'sub_groups' => collect(self::TYPES)->keys()
                ->mapWithKeys(fn ($type) => [$type => $this->getSubGroupOptions($type)])->all(),
            'filter_labels' => $this->getFilterLabels(),
        ];
    }
}
