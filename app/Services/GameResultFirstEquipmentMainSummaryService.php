<?php

namespace App\Services;

use App\Models\GameResultFirstEquipmentMainSummary;
use App\Models\VersionHistory;
use App\Traits\ErDevTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GameResultFirstEquipmentMainSummaryService
{
    use ErDevTrait;
    protected RankRangeService $rankRangeService;
    protected GameResultService $gameResultService;
    public function __construct()
    {
        $this->rankRangeService = new RankRangeService();
        $this->gameResultService = new GameResultService();
    }

    /**
     * 게임 결과 데이터 삽입
     * @return void
     */
    public function updateGameResultFirstEquipmentMainSummary($versionSeason = null, $versionMajor = null, $versionMinor = null)
    {
        Log::channel('updateGameResultFirstEquipmentMainSummary')->info('S: game equipment main result summary');

        $latestVersion = VersionHistory::latest('created_at')->first();
        $versionSeason = $versionSeason ?? $latestVersion->version_season;
        $versionMajor = $versionMajor ?? $latestVersion->version_major;
        $versionMinor = $versionMinor ?? $latestVersion->version_minor;

        $tiers = $this->tierRange;

        // ✅ **트랜잭션 시작**
        DB::beginTransaction();
        try {
            // ✅ **기존 데이터 삭제**
            GameResultFirstEquipmentMainSummary::where('version_season', $versionSeason)
                ->where('version_major', $versionMajor)
                ->where('version_minor', $versionMinor)
                ->delete();

            $bulkInsertData = [];

            foreach ($tiers as $tier) {
                $versionFilters = [
                    'version_season' => $versionSeason,
                    'version_major' => $versionMajor,
                    'version_minor' => $versionMinor
                ];
                $minScore = $this->rankRangeService->getMinScore($tier['tier'], $tier['tierNumber'], $versionFilters) ?: 0;
                $minTier = $tier['tier'].$tier['tierNumber'];
                echo $tier['tier'] . $tier['tierNumber'] . ':' . $minScore . "\n";
                $gameResults = $this->gameResultService->getGameResultFirstEquipmentMain([
                    'version_season' => $versionSeason,
                    'version_major' => $versionMajor,
                    'version_minor' => $versionMinor,
                    'min_tier' => $minTier,
                    'min_score' => $minScore,
                ]);

                $bulkInsertData = []; // Initialize chunk array inside the tier loop
                $chunkSize = 100; // Define chunk size

                $gameResultsCursor = $gameResults['data'];
                foreach ($gameResultsCursor as $gameResult) {
                    $bulkInsertData[] = [
                        'equipment_id' => $gameResult['equipmentId'],
                        'equipment_name' => $gameResult['name'],
                        'meta_tier' => $gameResult['metaTier'],
                        'meta_score' => $gameResult['metaScore'],
                        'game_count' => $gameResult['gameCount'],
                        'min_tier' => $minTier,
                        'min_score' => $minScore,
                        'positive_game_count' => $gameResult['positiveGameCount'],
                        'negative_game_count' => $gameResult['negativeGameCount'],
                        'game_count_percent' => $gameResult['gameCountPercent'],
                        'positive_game_count_percent' => $gameResult['positiveGameCountPercent'],
                        'negative_game_count_percent' => $gameResult['negativeGameCountPercent'],
                        'top1_count' => $gameResult['top1Count'],
                        'top2_count' => $gameResult['top2Count'],
                        'top4_count' => $gameResult['top4Count'],
                        'top1_count_percent' => $gameResult['top1CountPercent'],
                        'top2_count_percent' => $gameResult['top2CountPercent'],
                        'top4_count_percent' => $gameResult['top4CountPercent'],
                        'endgame_win_percent' => $gameResult['endgameWinPercent'],
                        'avg_mmr_gain' => $gameResult['avgMmrGain'],
                        'avg_team_kill_score' => $gameResult['avgTeamKillScore'],
                        'positive_avg_mmr_gain' => $gameResult['positiveAvgMmrGain'],
                        'negative_avg_mmr_gain' => $gameResult['negativeAvgMmrGain'],
                        'version_season' => $versionSeason,
                        'version_major' => $versionMajor,
                        'version_minor' => $versionMinor,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ];
                    // Insert chunk when it reaches the defined size
                    if (count($bulkInsertData) >= $chunkSize) {
                        GameResultFirstEquipmentMainSummary::insert($bulkInsertData);
                        $bulkInsertData = []; // Reset chunk array
                    }
                }


                // Insert any remaining data in the last chunk
                if (!empty($bulkInsertData)) {
                    GameResultFirstEquipmentMainSummary::insert($bulkInsertData);
                }
            }

            // ✅ **트랜잭션 커밋**
            DB::commit();
            Log::channel('updateGameResultFirstEquipmentMainSummary')->info('E: game equipment main result summary');
        } catch (\Exception $e) {
            // ❌ **오류 발생 시 롤백**
            DB::rollBack();
            Log::channel('updateGameResultFirstEquipmentMainSummary')->error('Error: ' . $e->getMessage());
            Log::channel('updateGameResultFirstEquipmentMainSummary')->error($e->getTraceAsString()); // 💡 스택트레이스 추가
            throw $e;
        }
    }

    public function getList(array $filters)
    {
        // 초기 장비 페이지용: 랭킹 계산 제거로 성능 최적화
        $results = GameResultFirstEquipmentMainSummary::select(
            'game_results_first_equipment_main_summary.*',
            'equipments.item_grade',
            'equipments.item_type2',
            // 장비 스탯 정보 추가
            'equipments.attack_power', 'equipments.attack_power_by_lv',
            'equipments.defense', 'equipments.defense_by_lv',
            'equipments.skill_amp', 'equipments.skill_amp_by_lv',
            'equipments.skill_amp_ratio', 'equipments.skill_amp_ratio_by_lv',
            'equipments.adaptive_force', 'equipments.adaptive_force_by_lv',
            'equipments.max_hp', 'equipments.max_hp_by_lv',
            'equipments.hp_regen', 'equipments.hp_regen_ratio',
            'equipments.sp_regen', 'equipments.sp_regen_ratio',
            'equipments.attack_speed_ratio', 'equipments.attack_speed_ratio_by_lv',
            'equipments.critical_strike_chance', 'equipments.critical_strike_damage',
            'equipments.cooldown_reduction',
            'equipments.life_steal', 'equipments.normal_life_steal', 'equipments.skill_life_steal',
            'equipments.move_speed', 'equipments.move_speed_ratio', 'equipments.move_speed_out_of_combat',
            'equipments.penetration_defense', 'equipments.penetration_defense_ratio'
        )
            ->join('equipments', 'equipments.id', '=', 'game_results_first_equipment_main_summary.equipment_id')
            ->where($filters)
            ->whereIn('equipments.item_grade', ['Epic'])
            ->orderBy('meta_score', 'desc')
            ->get();

        // 장비 스탯 정보와 스킬 정보를 배열로 변환하여 추가
        foreach ($results as $result) {
            $result->equipment_stats = $this->formatEquipmentStats($result);
            $result->equipment_skills = $this->getEquipmentSkills($result->equipment_id);
        }

        return $results;
    }

    /**
     * 장비 스탯을 포맷팅하여 반환
     */
    private function formatEquipmentStats($equipment): array
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
            $valueByLv = $equipment->{$key . '_by_lv'} ?? 0;

            // 백분율 스탯 확인
            $isPercentage = strpos($key, 'ratio') !== false ||
                $key === 'critical_strike_chance' ||
                $key === 'critical_strike_damage' ||
                $key === 'cooldown_reduction' ||
                $key === 'unique_cooldown_limit' ||
                $key === 'life_steal' ||
                $key === 'normal_life_steal' ||
                $key === 'skill_life_steal' ||
                $key === 'unique_life_steal' ||
                $key === 'unique_tenacity';

            // 기본 스탯
            if ($value != 0) {
                if ($isPercentage) {
                    $displayValue = $value;
                    if ($key != 'cooldown_reduction' && $key != 'unique_cooldown_limit' &&
                        $key != 'penetration_defense_ratio' && $key != 'unique_penetration_defense_ratio') {
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
                    'value' => $displayValue
                ];
            }

            // 레벨당 증가 스탯 (별도 행으로)
            if ($valueByLv != 0) {
                if ($isPercentage) {
                    $displayValue = $valueByLv;
                    if ($key != 'cooldown_reduction' && $key != 'unique_cooldown_limit' &&
                        $key != 'penetration_defense_ratio' && $key != 'unique_penetration_defense_ratio') {
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
