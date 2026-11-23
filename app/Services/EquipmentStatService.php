<?php

namespace App\Services;

use App\Traits\ErDevTrait;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 장비별 지표 (관리자 전용)
 *
 * TOP4 상세 지표가 캐릭터 x 무기 x 아이템 단위라면, 여기서는 아이템 하나를 1행으로 접는다.
 * 지표 구성(TOP4 / 전체)은 TopRankStatService 와 같은 SELECT 절을 쓴다.
 *
 * 추가로 "이 아이템을 가장 많이 쓰는 캐릭터"를 붙인다.
 * 단순 게임수가 아니라 **캐릭터 사용률** 순이다.
 *   사용률 = (그 캐릭터·무기가 이 아이템을 든 게임수) / (그 캐릭터·무기의 전체 게임수)
 * 게임수로 정렬하면 픽률 높은 캐릭터만 위에 올라와서, 실제로 "이 아이템을 주로 쓰는" 캐릭터가 안 보인다.
 *
 * 분모는 같은 버전·티어의 game_results_rank_summary (캐릭터 x 무기 x 순위) 합계다.
 * 별도 집계 테이블은 만들지 않는다.
 */
class EquipmentStatService
{
    use ErDevTrait;

    /** 행마다 내려보내는 주 사용 캐릭터 수 (화면은 3개만 먼저 보여주고 펼치면 전부) */
    private const TOP_CHARACTER_LIMIT = 10;

    public function getStats(array $filters): array
    {
        $cacheKey = 'equipment_stat_' . md5(json_encode($filters));

        return Cache::remember($cacheKey, 600, fn () => $this->queryStats($filters));
    }

    private function queryStats(array $filters): array
    {
        $versionParts = parse_version_key($filters['version'] ?? null);
        $tableName = VersionedGameTableManager::getTableName('game_results_equipment_summary', $versionParts);
        $rankTableName = VersionedGameTableManager::getTableName('game_results_rank_summary', $versionParts);

        if (!Schema::hasTable($tableName)) {
            return ['data' => [], 'table' => $tableName, 'exists' => false];
        }

        $minTier = $filters['min_tier'] ?? 'Diamond';
        $groupSelect = "CASE WHEN n.item_type1 = 'Weapon' THEN 'Weapon' ELSE n.item_type2 END";

        // 1) 아이템 단위 지표
        $query = DB::table($tableName . ' as s')
            ->leftJoin('equipments as n', 'n.id', '=', 's.equipment_id')
            ->where('s.min_tier', $minTier)
            ->groupBy('s.equipment_id', 'n.name', 'n.item_grade', DB::raw($groupSelect))
            ->select([
                DB::raw('s.equipment_id as item_id'),
                DB::raw('n.name as item_name'),
                DB::raw($groupSelect . ' as group_code'),
                DB::raw('n.item_grade as sub_group_code'),
                ...TopRankStatService::metricSelects('s'),
            ]);
        $this->applyFilters($query, $filters, $groupSelect);

        $minGameCount = (int) ($filters['min_game_count'] ?? 0);
        if ($minGameCount > 0) {
            $query->havingRaw('SUM(s.game_rank_count) >= ?', [$minGameCount]);
        }

        $rows = $query->orderByRaw('SUM(s.game_rank_count) DESC')->limit(500)->get();

        // 2) 아이템 x 캐릭터 x 무기 사용 게임수 → 사용률 상위 캐릭터
        $topCharacters = $rows->isEmpty()
            ? []
            : $this->topCharacters($tableName, $rankTableName, $filters, $groupSelect, $rows->pluck('item_id')->all());

        return [
            'data' => $rows->map(fn ($row) => [
                'item_id' => $row->item_id,
                'item_name' => $row->item_name ?? ('#' . $row->item_id),
                'image' => "/storage/Equipment/{$row->item_id}.png",
                'group_code' => $row->group_code ?? '',
                'group_label' => $row->group_code ? $this->replaceItemType2($row->group_code, 'ko') : '',
                'sub_group_code' => $row->sub_group_code ?? '',
                'sub_group_label' => $row->sub_group_code ? $this->replaceItemGrade($row->sub_group_code, 'ko') : '',
                ...TopRankStatService::formatMetrics($row),
                'top_characters' => $topCharacters[$row->item_id] ?? [],
            ])->all(),
            'table' => $tableName,
            'exists' => true,
            'weapon_types' => $this->availableWeaponTypes($tableName, $filters),
        ];
    }

    /**
     * 아이템별 사용률 상위 캐릭터·무기
     *
     * @return array<int, array> [item_id => [ {character_id, character_name, weapon_type, weapon_label,
     *                                          game_count, total_count, usage_rate, avg_mmr_gain, top_rate}, ... ]]
     */
    private function topCharacters(string $tableName, string $rankTableName, array $filters, string $groupSelect, array $itemIds): array
    {
        $minTier = $filters['min_tier'] ?? 'Diamond';
        $top = 4;

        $query = DB::table($tableName . ' as s')
            ->leftJoin('equipments as n', 'n.id', '=', 's.equipment_id')
            ->where('s.min_tier', $minTier)
            ->whereIn('s.equipment_id', $itemIds)
            ->groupBy('s.equipment_id', 's.character_id', 's.weapon_type')
            ->select([
                's.equipment_id',
                's.character_id',
                's.weapon_type',
                DB::raw('SUM(s.game_rank_count) as game_count'),
                DB::raw("SUM(CASE WHEN s.game_rank <= {$top} THEN s.game_rank_count ELSE 0 END) as top_count"),
                DB::raw('SUM(s.avg_mmr_gain * s.game_rank_count) / NULLIF(SUM(s.game_rank_count), 0) as avg_mmr_gain'),
            ]);
        $this->applyFilters($query, $filters, $groupSelect);

        // 표본이 너무 적은 캐릭터는 사용률이 튄다 (2게임 중 2게임 = 100%)
        $minCharacterGames = max(1, (int) ($filters['min_character_game_count'] ?? 10));
        $query->havingRaw('SUM(s.game_rank_count) >= ?', [$minCharacterGames]);

        $usages = $query->get();

        // 분모: 캐릭터·무기별 전체 게임수
        $totals = [];
        if (Schema::hasTable($rankTableName)) {
            $totals = DB::table($rankTableName)
                ->where('min_tier', $minTier)
                ->groupBy('character_id', 'weapon_type')
                ->select('character_id', 'weapon_type', DB::raw('SUM(game_rank_count) as total'))
                ->get()
                ->mapWithKeys(fn ($r) => [$r->character_id . '|' . $r->weapon_type => (int) $r->total])
                ->all();
        }

        $characterNames = DB::table('characters')->pluck('name', 'id')->all();

        $result = [];
        foreach ($usages as $u) {
            $total = $totals[$u->character_id . '|' . $u->weapon_type] ?? 0;
            if ($total <= 0) {
                continue;
            }
            $gameCount = (int) $u->game_count;

            $result[$u->equipment_id][] = [
                'character_id' => $u->character_id,
                'character_name' => $characterNames[$u->character_id] ?? ('#' . $u->character_id),
                'weapon_type' => $u->weapon_type,
                'weapon_label' => $this->replaceWeaponType($u->weapon_type, 'ko'),
                'game_count' => $gameCount,
                'total_count' => $total,
                // 아이템은 부위별 최종 장비라 한 게임에 1번만 잡힌다 → 100% 를 넘지 않는다
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

    private function applyFilters($query, array $filters, string $groupSelect): void
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
            $query->where('n.item_grade', $filters['sub_group']);
        }

        if (!empty($filters['search'])) {
            $query->where('n.name', 'like', '%' . $filters['search'] . '%');
        }
    }

    private function availableWeaponTypes(string $tableName, array $filters): array
    {
        $query = DB::table($tableName)->where('min_tier', $filters['min_tier'] ?? 'Diamond');

        if (!empty($filters['character_id'])) {
            $query->where('character_id', $filters['character_id']);
        }

        return $query->distinct()->orderBy('weapon_type')->pluck('weapon_type')
            ->filter()
            ->map(fn ($code) => ['value' => $code, 'label' => $this->replaceWeaponType($code, 'ko')])
            ->values()->all();
    }
}
