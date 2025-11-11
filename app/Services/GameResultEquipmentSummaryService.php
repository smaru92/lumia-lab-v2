<?php

namespace App\Services;

use App\Models\GameResultEquipmentSummary;
use App\Traits\ErDevTrait;

class GameResultEquipmentSummaryService extends BaseSummaryService
{
    use ErDevTrait;

    public function __construct()
    {
        parent::__construct('updateGameResultEquipmentSummary');
    }

    public function updateGameResultEquipmentSummary($versionSeason = null, $versionMajor = null, $versionMinor = null)
    {
        $this->updateSummary($versionSeason, $versionMajor, $versionMinor);
    }

    protected function getSummaryModel(): string
    {
        return GameResultEquipmentSummary::class;
    }

    protected function getGameResults(array $params): iterable
    {
        return $this->gameResultService->getGameResultByEquipment($params);
    }

    protected function transformData(object|array $gameResult, string $minTier, int $minScore, int $versionSeason, int $versionMajor, int $versionMinor): array
    {
        return [
            'character_id' => $gameResult->character_id,
            'equipment_id' => $gameResult->equipment_id,
            'weapon_type' => $gameResult->weapon_type,
            'game_rank' => $gameResult->game_rank,
            'game_rank_count' => $gameResult->game_rank_count,
            'avg_mmr_gain' => $gameResult->avg_mmr_gain,
            'positive_count' => $gameResult->positive_count,
            'negative_count' => $gameResult->negative_count,
            'positive_avg_mmr_gain' => $gameResult->positive_avg_mmr_gain,
            'negative_avg_mmr_gain' => $gameResult->negative_avg_mmr_gain,
            'min_tier' => $minTier,
            'min_score' => $minScore,
            'version_season' => $versionSeason,
            'version_major' => $versionMajor,
            'version_minor' => $versionMinor,
            'updated_at' => now(),
            'created_at' => now(),
        ];
    }

    public function getDetail(array $filters)
    {
        $filters['weapon_type'] = $this->replaceWeaponType($filters['weapon_type'], 'en');
        if (isset($filters['character_name'])) {
            $filters['c.name'] = $filters['character_name'];
            unset($filters['character_name']);
        }

        // 캐시 키 생성
        $cacheKey = "equipment_summary_" . md5(json_encode($filters));
        $cacheDuration = 60 * 10; // 10분 캐싱

        $data = cache()->remember($cacheKey, $cacheDuration, function () use ($filters) {
            return GameResultEquipmentSummary::select(
            'c.name as character_name',
            'e.item_type1',
            'e.item_type2',
            'e.item_grade',
            'e.name as equipment_name',
            'e.attack_power',
            'e.attack_power_by_lv',
            'e.defense',
            'e.defense_by_lv',
            'e.skill_amp',
            'e.skill_amp_by_lv',
            'e.skill_amp_ratio',
            'e.skill_amp_ratio_by_lv',
            'e.adaptive_force',
            'e.adaptive_force_by_lv',
            'e.max_hp',
            'e.max_hp_by_lv',
            'e.mas_sp',
            'e.mas_sp_by_lv',
            'e.hp_regen',
            'e.hp_regen_ratio',
            'e.sp_regen',
            'e.sp_regen_ratio',
            'e.attack_speed_ratio',
            'e.attack_speed_ratio_by_lv',
            'e.critical_strike_chance',
            'e.critical_strike_damage',
            'e.prevent_critical_strike_damaged',
            'e.cooldown_reduction',
            'e.cooldown_limit',
            'e.life_steal',
            'e.normal_life_steal',
            'e.skill_life_steal',
            'e.move_speed',
            'e.move_speed_ratio',
            'e.move_speed_out_of_combat',
            'e.sight_range',
            'e.attack_range',
            'e.increase_basic_attack_damage',
            'e.increase_basic_attack_damage_by_lv',
            'e.increase_basic_attack_damage_ratio',
            'e.increase_basic_attack_damage_ratio_by_lv',
            'e.prevent_basic_attack_damaged',
            'e.prevent_basic_attack_damaged_by_lv',
            'e.prevent_basic_attack_damaged_ratio',
            'e.prevent_basic_attack_damaged_ratio_by_lv',
            'e.prevent_skill_damaged',
            'e.prevent_skill_damaged_by_lv',
            'e.prevent_skill_damaged_ratio',
            'e.prevent_skill_damaged_ratio_by_lv',
            'e.penetration_defense',
            'e.penetration_defense_ratio',
            'e.trap_damage_reduce',
            'e.trap_damage_reduce_ratio',
            'e.slow_resist_ratio',
            'e.hp_healed_increase_ratio',
            'e.healer_give_hp_heal_ratio',
            'e.unique_attack_range',
            'e.unique_hp_healed_increase_ratio',
            'e.unique_cooldown_limit',
            'e.unique_tenacity',
            'e.unique_move_speed',
            'e.unique_penetration_defense',
            'e.unique_penetration_defense_ratio',
            'e.unique_life_steal',
            'e.unique_skill_amp_ratio',
            'game_results_equipment_summary.*',
        )
            ->join('equipments as e', 'e.id', 'game_results_equipment_summary.equipment_id')
            ->join('characters as c', 'c.id', 'game_results_equipment_summary.character_id')
            ->where($filters)
            ->orderBy('game_rank_count', 'desc')
            ->orderBy('game_results_equipment_summary.game_rank', 'asc')
            ->get();
        });
        $total = array();
        $result = array(
            'Weapon' => array(),
            'Chest' => array(),
            'Head' => array(),
            'Arm' => array(),
            'Leg' => array(),
        );
        foreach ($data as $item) {
            if (!isset($total[$item->equipment_id])) {
                $total[$item->equipment_id] = 0;
            }
            $total[$item->equipment_id] += $item->game_rank_count;
            $item->positive_count_percent = $item->game_rank_count ? $item->positive_count / $item->game_rank_count * 100 : 0;
            $item->negative_count_percent = $item->game_rank_count ? $item->negative_count / $item->game_rank_count * 100 : 0;
        }
        foreach ($data as $item) {
            $item->game_rank_count_percent = $total[$item->equipment_id] ? $item->game_rank_count / $total[$item->equipment_id] * 100 : 0;
            $item->weapon_type = $this->replaceWeaponType($item->weapon_type, 'ko');
            $item->equipment_stats = $this->setEquipmtStat($item);
            $item->equipment_skills = $this->getEquipmentSkills($item->equipment_id);
            if ($item->item_type1 === 'Weapon') {
                $itemType = 'Weapon';
            } else {
                $itemType = $item->item_type2;
            }
            if (!isset($result[$itemType][$item->equipment_id])) {
                $result[$itemType][$item->equipment_id] = array();
                foreach(range(1, 4) as $rank) {
                    $result[$itemType][$item->equipment_id][$rank] = (object) [
                        "character_name" => $item->character_name,
                        "item_type1" => $item->item_type1,
                        "item_type2" => $item->item_type2,
                        "item_grade" => $item->item_grade,
                        "equipment_name" => $item->equipment_name,
                        "equipment_stats" => $item->equipment_stats,
                        "equipment_skills" => $item->equipment_skills,
                        "id" => 0,
                        "equipment_id" => $item->equipment_id,
                        "character_id" => $item->character_id,
                        "weapon_type" => $item->weapon_type,
                        "game_rank" => $rank,
                        "game_rank_count" => 0,
                        "positive_count" => 0,
                        "negative_count" => 0,
                        "avg_mmr_gain" => 0,
                        "positive_avg_mmr_gain" => 0,
                        "negative_avg_mmr_gain" => 0,
                        "min_tier" => $item->min_tier,
                        "min_score" => $item->min_score,
                        "version_major" => $item->version_major,
                        "version_minor" => $item->version_minor,
                        "created_at" => "0000-00-00 00:00:00",
                        "updated_at" => "0000-00-00 00:00:00",
                        "positive_count_percent" => 0,
                        "negative_count_percent" => 0,
                        "game_rank_count_percent" => 0,
                    ];
                }
            }
            $result[$itemType][$item->equipment_id][$item->game_rank] = $item;

        }

        // Sort each item type by total usage count
        foreach ($result as $itemType => $items) {
            uksort($result[$itemType], function($idA, $idB) use ($total) {
                $totalA = isset($total[$idA]) ? $total[$idA] : 0;
                $totalB = isset($total[$idB]) ? $total[$idB] : 0;
                return $totalB - $totalA;
            });
        }

        return [
            'data' => $result,
            'total' => $total
        ];
    }

    private function setEquipmtStat($equipment)
    {
        $fields = [
            'attack_power' => '공격력',
            'attack_power_by_lv' => '레벨당 공격력',
            'defense' => '방어력',
            'defense_by_lv' => '레벨당 방어력',
            'skill_amp' => '스킬증폭',
            'skill_amp_by_lv' => '레벨당 스킬증폭',
            'skill_amp_ratio' => '스킬증폭',
            'skill_amp_ratio_by_lv' => '레벨당 스킬증폭',
            'adaptive_force' => '적응형 능력치',
            'adaptive_force_by_lv' => '레벨당 적응형 능력치',
            'max_hp' => '최대체력',
            'max_hp_by_lv' => '레벨당 최대체력',
            'mas_sp' => '최대스테미나',
            'mas_sp_by_lv' => '레벨당 최대스테미나',
            'hp_regen' => '체력회복',
            'hp_regen_ratio' => '체력회복',
            'sp_regen' => '스테미나회복',
            'sp_regen_ratio' => '스테미나회복',
            'attack_speed_ratio' => '공격속도',
            'attack_speed_ratio_by_lv' => '레벨당 공격속도',
            'critical_strike_chance' => '치명타확률',
            'critical_strike_damage' => '치명타피해',
            'prevent_critical_strike_damaged' => '받는 치명타피해감소',
            'cooldown_reduction' => '쿨다운감소',
            'cooldown_limit' => '최대쿨다운감소',
            'life_steal' => '체력흡혈',
            'normal_life_steal' => '일반공격 체력흡혈',
            'skill_life_steal' => '스킬 체력흡혈',
            'move_speed' => '이동속도',
            'move_speed_ratio' => '이동속도',
            'move_speed_out_of_combat' => '비전투 이동속도',
            'sight_range' => '시야',
            'attack_range' => '공격 사거리',
            'increase_basic_attack_damage' => '일반공격피해',
            'increase_basic_attack_damage_by_lv' => '레벨당 일반공격피해',
            'increase_basic_attack_damage_ratio' => '일반공격피해',
            'increase_basic_attack_damage_ratio_by_lv' => '레벨당 일반공격피해',
            'prevent_basic_attack_damaged' => '받는 일반공격피해 감소',
            'prevent_basic_attack_damaged_by_lv' => '레벨당 받는 일반공격피해 감소',
            'prevent_basic_attack_damaged_ratio' => '받는 일반공격피해 감소',
            'prevent_basic_attack_damaged_ratio_by_lv' => '레벨당 받는 일반공격피해 감소',
            'prevent_skill_damaged' => '받는 스킬피해 감소',
            'prevent_skill_damaged_by_lv' => '레벨당 받는 스킬피해 감소',
            'prevent_skill_damaged_ratio' => '받는 스킬피해 감소',
            'prevent_skill_damaged_ratio_by_lv' => '레벨당 받는 스킬피해 감소',
            'penetration_defense' => '방어관통',
            'penetration_defense_ratio' => '방어관통%',
            'trap_damage_reduce' => '받는 함정피해 감소',
            'trap_damage_reduce_ratio' => '받는 함정피해 감소',
            'slow_resist_ratio' => '둔화저항',
            'hp_healed_increase_ratio' => '체력회복',
            'healer_give_hp_heal_ratio' => '주는 체력회복',
            'unique_attack_range' => '(고유)공격 사거리',
            'unique_hp_healed_increase_ratio' => '(고유)체력회복',
            'unique_cooldown_limit' => '(고유)최대 쿨다운감소',
            'unique_tenacity' => '(고유)강인함',
            'unique_move_speed' => '(고유)이동속도',
            'unique_penetration_defense' => '(고유)방어관통',
            'unique_penetration_defense_ratio' => '(고유)방어관통',
            'unique_life_steal' => '(고유)체력흡혈',
            'unique_skill_amp_ratio' => '(고유)스킬증폭',
        ];

        $equipmentStat = [];

        foreach ($fields as $key => $label) {
            $value = $equipment->$key ?? null;
            if ($value !== null && floatval($value) != 0.0) {
                $ratioKeywords = ['ratio', 'cooldown', 'steal', 'critical'];
                $decimalKeyword = ['move_speed'];
                if (array_filter($ratioKeywords, fn($word) => str_contains($value, $word))) {
                    $value = number_format($value * 100) . '%';
                } elseif (array_filter($decimalKeyword, fn($word) => str_contains($value, $word))) {
                    $value = number_format($value, 2);
                } else {
                    $value = number_format($value, 2);
                }
                $equipmentStat[] = [
                    'text' => $label,
                    'value' => $value
                ];
            }
        }
        return $equipmentStat;
    }

    /**
     * 장비 스킬 정보를 가져옴
     */
    private function getEquipmentSkills($equipmentId): array
    {
        $skills = DB::table('equipment_equipment_skill')
            ->join('equipment_skills', 'equipment_equipment_skill.equipment_skill_id', '=', 'equipment_skills.id')
            ->where('equipment_equipment_skill.equipment_id', $equipmentId)
            ->select('equipment_skills.name', 'equipment_skills.description')
            ->get();

        return $skills->map(function ($skill) {
            return [
                'name' => $skill->name,
                'description' => $skill->description ?? ''
            ];
        })->toArray();
    }

}
