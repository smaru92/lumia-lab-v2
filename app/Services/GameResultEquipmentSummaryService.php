<?php

namespace App\Services;

use App\Models\GameResultEquipmentSummary;
use App\Models\VersionHistory;
use App\Traits\ErDevTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

    protected function getSummaryTableBaseName(): string
    {
        return 'game_results_equipment_summary';
    }

    protected function ensureTableExists(string $tableName): void
    {
        $this->versionedTableManager->ensureGameResultEquipmentSummaryTableExists($tableName);
    }

    protected function getGameResults(array $params): iterable
    {
        return $this->gameResultService->getGameResultByEquipment($params);
    }

    protected function transformData(object|array $gameResult, string $minTier, int $minScore): array
    {
        return [
            'character_id' => $gameResult->character_id,
            'equipment_id' => $gameResult->equipment_id,
            'weapon_type' => $gameResult->weapon_type,
            'game_rank' => $gameResult->game_rank,
            'game_rank_count' => $gameResult->game_rank_count,
            'avg_mmr_gain' => $gameResult->avg_mmr_gain,
            'avg_team_kill_score' => $gameResult->avg_team_kill_score ?? null,
            'positive_count' => $gameResult->positive_count,
            'negative_count' => $gameResult->negative_count,
            'positive_avg_mmr_gain' => $gameResult->positive_avg_mmr_gain,
            'negative_avg_mmr_gain' => $gameResult->negative_avg_mmr_gain,
            'min_tier' => $minTier,
            'min_score' => $minScore,
            'updated_at' => now(),
            'created_at' => now(),
        ];
    }

    protected function getVersionedTableName(array $filters): string
    {
        $versionSeason = $filters['version_season'] ?? null;
        $versionMajor = $filters['version_major'] ?? null;
        $versionMinor = $filters['version_minor'] ?? null;

        if (!$versionSeason || !$versionMajor || !$versionMinor) {
            $latestVersion = VersionHistory::latest('created_at')->first();
            $versionSeason = $versionSeason ?? $latestVersion->version_season;
            $versionMajor = $versionMajor ?? $latestVersion->version_major;
            $versionMinor = $versionMinor ?? $latestVersion->version_minor;
        }

        return VersionedGameTableManager::getTableName($this->getSummaryTableBaseName(), [
            'version_season' => $versionSeason,
            'version_major' => $versionMajor,
            'version_minor' => $versionMinor,
        ]);
    }

    public function getDetail(array $filters)
    {
        $tableName = $this->getVersionedTableName($filters);

        // 테이블 존재 여부 확인
        if (!Schema::hasTable($tableName)) {
            return [
                'data' => [],
                'total' => [],
                'aggregatedData' => [
                    'Weapon' => [],
                    'Chest' => [],
                    'Head' => [],
                    'Arm' => [],
                    'Leg' => [],
                ]
            ];
        }

        $filters['weapon_type'] = $this->replaceWeaponType($filters['weapon_type'], 'en');
        if (isset($filters['character_name'])) {
            $filters['c.name'] = $filters['character_name'];
            unset($filters['character_name']);
        }
        unset($filters['version_season'], $filters['version_major'], $filters['version_minor']);

        // 캐시 키 생성
        $cacheKey = "equipment_summary_" . md5(json_encode($filters) . $tableName);
        $cacheDuration = 60 * 10; // 10분 캐싱

        $data = cache()->remember($cacheKey, $cacheDuration, function () use ($filters, $tableName) {
            return DB::table($tableName . ' as ges')
                ->select(
                    'c.name as character_name',
                    'e.item_type1',
                    'e.item_type2',
                    'e.item_type3',
                    'e.item_grade',
                    'e.name as equipment_name',
                    'e.attack_power',
                    'e.attack_power_by_lv',
                    'e.defense',
                    'e.defense_by_lv',
                    'e.skill_amp',
                    'e.skill_amp_by_level',
                    'e.skill_amp_ratio',
                    'e.skill_amp_ratio_by_level',
                    'e.adaptive_force',
                    'e.adaptive_force_by_level',
                    'e.max_hp',
                    'e.max_hp_by_lv',
                    'e.max_sp',
                    'e.max_sp_by_lv',
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
                    'ges.*'
                )
                ->join('equipments as e', 'e.id', 'ges.equipment_id')
                ->join('characters as c', 'c.id', 'ges.character_id')
                ->where($filters)
                ->whereIn('e.item_grade', ['Epic', 'Legend', 'Mythic'])
                ->orderBy('game_rank_count', 'desc')
                ->orderBy('ges.game_rank', 'asc')
                ->get();
        });

        // 🔥 최적화 1: 장비 ID 수집하여 한 번에 스킬 정보 가져오기 (N+1 쿼리 해결)
        $equipmentIds = $data->pluck('equipment_id')->unique()->toArray();
        $equipmentSkillsMap = $this->getEquipmentSkillsBulk($equipmentIds);

        $total = array();
        $result = array(
            'Weapon' => array(),
            'Chest' => array(),
            'Head' => array(),
            'Arm' => array(),
            'Leg' => array(),
        );

        // 🔥 최적화 2: total 계산 먼저 수행
        foreach ($data as $item) {
            if (!isset($total[$item->equipment_id])) {
                $total[$item->equipment_id] = 0;
            }
            $total[$item->equipment_id] += $item->game_rank_count;
        }

        // 메인 처리 루프
        foreach ($data as $item) {
            // 퍼센트 계산
            $item->positive_count_percent = $item->game_rank_count ? $item->positive_count / $item->game_rank_count * 100 : 0;
            $item->negative_count_percent = $item->game_rank_count ? $item->negative_count / $item->game_rank_count * 100 : 0;
            $item->game_rank_count_percent = $total[$item->equipment_id] ? $item->game_rank_count / $total[$item->equipment_id] * 100 : 0;

            $item->weapon_type = $this->replaceWeaponType($item->weapon_type, 'ko');
            $item->equipment_stats = $this->setEquipmtStat($item);
            // 🔥 최적화 3: 미리 로드한 스킬 정보 사용 (N+1 쿼리 해결)
            $item->equipment_skills = $equipmentSkillsMap[$item->equipment_id] ?? [];

            if ($item->item_type1 === 'Weapon') {
                $itemType = 'Weapon';
            } else {
                $itemType = $item->item_type2;
            }

            if (!isset($result[$itemType][$item->equipment_id])) {
                $result[$itemType][$item->equipment_id] = array();
                // 🔥 최적화 4: 빈 랭크 객체 생성 최적화
                $emptyRankTemplate = [
                    "character_name" => $item->character_name,
                    "item_type1" => $item->item_type1,
                    "item_type2" => $item->item_type2,
                    "item_type3" => $item->item_type3,
                    "item_grade" => $item->item_grade,
                    "equipment_name" => $item->equipment_name,
                    "equipment_stats" => $item->equipment_stats,
                    "equipment_skills" => $item->equipment_skills,
                    "id" => 0,
                    "equipment_id" => $item->equipment_id,
                    "character_id" => $item->character_id,
                    "weapon_type" => $item->weapon_type,
                    "game_rank_count" => 0,
                    "positive_count" => 0,
                    "negative_count" => 0,
                    "avg_mmr_gain" => 0,
                    "positive_avg_mmr_gain" => 0,
                    "negative_avg_mmr_gain" => 0,
                    "min_tier" => $item->min_tier,
                    "min_score" => $item->min_score,
                    "created_at" => "0000-00-00 00:00:00",
                    "updated_at" => "0000-00-00 00:00:00",
                    "positive_count_percent" => 0,
                    "negative_count_percent" => 0,
                    "game_rank_count_percent" => 0,
                ];

                foreach(range(1, 4) as $rank) {
                    $emptyRank = $emptyRankTemplate;
                    $emptyRank['game_rank'] = $rank;
                    $result[$itemType][$item->equipment_id][$rank] = (object) $emptyRank;
                }
            }
            $result[$itemType][$item->equipment_id][$item->game_rank] = $item;
        }

        // Sort each item type by total usage count
        foreach ($result as $itemType => $items) {
            uksort($result[$itemType], function($idA, $idB) use ($total) {
                return ($total[$idB] ?? 0) - ($total[$idA] ?? 0);
            });
        }

        // 장비별 집계 데이터 생성 (특성 조합 통계와 동일한 형식)
        $aggregatedData = [
            'Weapon' => [],
            'Chest' => [],
            'Head' => [],
            'Arm' => [],
            'Leg' => [],
        ];

        foreach ($result as $itemType => $items) {
            foreach ($items as $equipmentId => $rankData) {
                $firstRank = $rankData[1] ?? $rankData[2] ?? $rankData[3] ?? $rankData[4] ?? null;
                if (!$firstRank) continue;

                $gameCount = 0;
                $top1Count = 0;
                $top2Count = 0;
                $top4Count = 0;
                $positiveCount = 0;
                $negativeCount = 0;
                $totalMmrGain = 0;
                $totalPositiveMmrGain = 0;
                $totalNegativeMmrGain = 0;
                $totalTeamKillScore = 0;
                $positiveGames = 0;
                $negativeGames = 0;

                foreach ($rankData as $rank => $item) {
                    $gameCount += $item->game_rank_count;
                    if ($rank == 1) $top1Count = $item->game_rank_count;
                    if ($rank <= 2) $top2Count += $item->game_rank_count;
                    if ($rank <= 4) $top4Count += $item->game_rank_count;
                    $positiveCount += $item->positive_count;
                    $negativeCount += $item->negative_count;
                    $totalMmrGain += $item->avg_mmr_gain * $item->game_rank_count;
                    $totalTeamKillScore += ($item->avg_team_kill_score ?? 0) * $item->game_rank_count;
                    if ($item->positive_count > 0) {
                        $totalPositiveMmrGain += ($item->positive_avg_mmr_gain ?? 0) * $item->positive_count;
                        $positiveGames += $item->positive_count;
                    }
                    if ($item->negative_count > 0) {
                        $totalNegativeMmrGain += ($item->negative_avg_mmr_gain ?? 0) * $item->negative_count;
                        $negativeGames += $item->negative_count;
                    }
                }

                $aggregatedData[$itemType][] = [
                    'equipment_id' => $equipmentId,
                    'equipment_name' => $firstRank->equipment_name,
                    'item_grade' => $firstRank->item_grade,
                    'item_type3' => $firstRank->item_type3 ?? null,
                    'equipment_stats' => $firstRank->equipment_stats,
                    'equipment_skills' => $firstRank->equipment_skills,
                    'game_count' => $gameCount,
                    'top1_count' => $top1Count,
                    'top2_count' => $top2Count,
                    'top4_count' => $top4Count,
                    'top1_count_percent' => $gameCount > 0 ? round($top1Count / $gameCount * 100, 2) : 0,
                    'top2_count_percent' => $gameCount > 0 ? round($top2Count / $gameCount * 100, 2) : 0,
                    'top4_count_percent' => $gameCount > 0 ? round($top4Count / $gameCount * 100, 2) : 0,
                    'avg_mmr_gain' => $gameCount > 0 ? round($totalMmrGain / $gameCount, 1) : 0,
                    'avg_team_kill_score' => $gameCount > 0 ? round($totalTeamKillScore / $gameCount, 2) : 0,
                    'positive_game_count' => $positiveCount,
                    'negative_game_count' => $negativeCount,
                    'positive_game_count_percent' => $gameCount > 0 ? round($positiveCount / $gameCount * 100, 2) : 0,
                    'negative_game_count_percent' => $gameCount > 0 ? round($negativeCount / $gameCount * 100, 2) : 0,
                    'positive_avg_mmr_gain' => $positiveGames > 0 ? round($totalPositiveMmrGain / $positiveGames, 1) : 0,
                    'negative_avg_mmr_gain' => $negativeGames > 0 ? round($totalNegativeMmrGain / $negativeGames, 1) : 0,
                    'endgame_win_percent' => $top2Count > 0 ? round($top1Count / $top2Count * 100, 2) : 0,
                ];
            }

            // 사용수 기준 정렬
            usort($aggregatedData[$itemType], function($a, $b) {
                return $b['game_count'] - $a['game_count'];
            });
        }

        return [
            'data' => $result,
            'total' => $total,
            'aggregatedData' => $aggregatedData
        ];
    }

    /**
     * 여러 장비의 스킬 정보를 한 번에 가져옴 (N+1 쿼리 최적화)
     */
    private function getEquipmentSkillsBulk(array $equipmentIds): array
    {
        if (empty($equipmentIds)) {
            return [];
        }

        $skills = DB::table('equipment_equipment_skill')
            ->join('equipment_skills', 'equipment_equipment_skill.equipment_skill_id', '=', 'equipment_skills.id')
            ->whereIn('equipment_equipment_skill.equipment_id', $equipmentIds)
            ->select('equipment_equipment_skill.equipment_id', 'equipment_skills.name', 'equipment_skills.description')
            ->get();

        // 장비 ID별로 그룹화
        $result = [];
        foreach ($skills as $skill) {
            if (!isset($result[$skill->equipment_id])) {
                $result[$skill->equipment_id] = [];
            }
            $result[$skill->equipment_id][] = [
                'name' => $skill->name,
                'description' => $skill->description ?? ''
            ];
        }

        return $result;
    }

    private function setEquipmtStat($equipment)
    {
        $stats = [];
        $statLabels = [
            'attack_power' => '공격력',
            'defense' => '방어력',
            'skill_amp' => '스킬 증폭',
            'skill_amp_ratio' => '스킬 증폭%',
            'adaptive_force' => '적응형 능력치',
            'max_hp' => '최대 체력',
            'hp_regen' => '체력 재생',
            'hp_regen_ratio' => '체력 재생%',
            'sp_regen' => '스태미나 재생',
            'sp_regen_ratio' => '스태미나 재생%',
            'attack_speed_ratio' => '공격 속도%',
            'critical_strike_chance' => '치명타 확률',
            'critical_strike_damage' => '치명타 피해',
            'cooldown_reduction' => '쿨다운 감소',
            'life_steal' => '생명력 흡수',
            'normal_life_steal' => '기본 공격 생명력 흡수',
            'skill_life_steal' => '스킬 생명력 흡수',
            'move_speed' => '이동 속도',
            'move_speed_ratio' => '이동 속도%',
            'move_speed_out_of_combat' => '비전투 이동 속도',
            'penetration_defense' => '방어 관통',
            'penetration_defense_ratio' => '방어 관통%',
            'increase_basic_attack_damage' => '일반 공격 피해',
            'increase_basic_attack_damage_ratio' => '일반 공격 피해%',
            'prevent_basic_attack_damaged' => '받는 일반 공격 피해 감소',
            'prevent_basic_attack_damaged_ratio' => '받는 일반 공격 피해 감소%',
            'prevent_skill_damaged' => '받는 스킬 피해 감소',
            'prevent_skill_damaged_ratio' => '받는 스킬 피해 감소%',
            'trap_damage_reduce' => '받는 함정 피해 감소',
            'trap_damage_reduce_ratio' => '받는 함정 피해 감소%',
            'slow_resist_ratio' => '둔화 저항%',
            'hp_healed_increase_ratio' => '체력 회복%',
            'healer_give_hp_heal_ratio' => '주는 체력 회복%',
            'unique_attack_range' => '(고유) 공격 사거리',
            'unique_hp_healed_increase_ratio' => '(고유) 체력 회복%',
            'unique_cooldown_limit' => '(고유) 최대 쿨다운 감소',
            'unique_tenacity' => '(고유) 강인함',
            'unique_move_speed' => '(고유) 이동 속도',
            'unique_penetration_defense' => '(고유) 방어 관통',
            'unique_penetration_defense_ratio' => '(고유) 방어 관통%',
            'unique_life_steal' => '(고유) 체력 흡수',
            'unique_skill_amp_ratio' => '(고유) 스킬 증폭%',
        ];

        foreach ($statLabels as $key => $label) {
            $value = $equipment->$key ?? 0;
            $valueByLv = $equipment->{$key . '_by_lv'} ?? $equipment->{$key . '_by_level'} ?? 0;

            // 백분율 스탯 확인
            $isPercentage = (strpos($key, 'ratio') !== false ||
                $key === 'critical_strike_chance' ||
                $key === 'critical_strike_damage' ||
                $key === 'cooldown_reduction' ||
                $key === 'unique_cooldown_limit' ||
                $key === 'life_steal' ||
                $key === 'normal_life_steal' ||
                $key === 'skill_life_steal' ||
                $key === 'unique_life_steal' ||
                $key === 'unique_tenacity') &&
                $key !== 'penetration_defense' &&
                $key !== 'unique_penetration_defense';

            // 기본 스탯
            if ($value != 0) {
                if ($isPercentage) {
                    $displayValue = $value;
                    if ($key != 'cooldown_reduction' && $key != 'unique_cooldown_limit') {
                        $displayValue *= 100;
                    }
                    $displayValue = number_format($displayValue);
                    $displayValue .= '%';
                } elseif($key == 'penetration_defense' || $key == 'unique_penetration_defense') {
                    $displayValue = number_format($value, 0);
                } elseif($key == 'move_speed' || $key == 'unique_move_speed') {
                    $displayValue = number_format($value, 2);
                } else {
                    $displayValue = number_format($value, 1);
                }

                $stats[] = [
                    'text' => $label,
                    'value' => '+' . $displayValue
                ];
            }

            // 레벨당 증가 스탯 (별도 행으로)
            if ($valueByLv != 0) {
                if ($isPercentage) {
                    $displayValue = $valueByLv;
                    if ($key != 'cooldown_reduction' && $key != 'unique_cooldown_limit') {
                        $displayValue *= 100;
                    }
                    $displayValue = number_format($displayValue);
                    $displayValue .= '%';
                } elseif($key == 'penetration_defense' || $key == 'unique_penetration_defense') {
                    $displayValue = number_format($valueByLv, 0);
                } elseif($key == 'move_speed' || $key == 'unique_move_speed') {
                    $displayValue = number_format($valueByLv, 2);
                } else {
                    $displayValue = number_format($valueByLv, 1);
                }

                $stats[] = [
                    'text' => '레벨당 ' . $label,
                    'value' => '+' . $displayValue
                ];
            }
        }

        return $stats;
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
