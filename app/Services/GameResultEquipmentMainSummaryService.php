<?php

namespace App\Services;

use App\Models\GameResultEquipmentMainSummary;
use App\Models\VersionHistory;
use App\Traits\ErDevTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class GameResultEquipmentMainSummaryService
{
    use ErDevTrait;
    protected RankRangeService $rankRangeService;
    protected GameResultService $gameResultService;
    protected VersionedGameTableManager $versionedTableManager;

    public function __construct()
    {
        $this->rankRangeService = new RankRangeService();
        $this->gameResultService = new GameResultService();
        $this->versionedTableManager = new VersionedGameTableManager();
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

        return VersionedGameTableManager::getTableName('game_results_equipment_main_summary', [
            'version_season' => $versionSeason,
            'version_major' => $versionMajor,
            'version_minor' => $versionMinor,
        ]);
    }

    /**
     * 게임 결과 데이터 삽입
     * @return void
     */
    public function updateGameResultEquipmentMainSummary($versionSeason = null, $versionMajor = null, $versionMinor = null)
    {
        Log::channel('updateGameResultEquipmentMainSummary')->info('S: game equipment main result summary');

        $latestVersion = VersionHistory::latest('created_at')->first();
        $versionSeason = $versionSeason ?? $latestVersion->version_season;
        $versionMajor = $versionMajor ?? $latestVersion->version_major;
        $versionMinor = $versionMinor ?? $latestVersion->version_minor;

        // 버전별 테이블명 생성
        $versionFilters = [
            'version_season' => $versionSeason,
            'version_major' => $versionMajor,
            'version_minor' => $versionMinor
        ];
        $tableName = VersionedGameTableManager::getTableName('game_results_equipment_main_summary', $versionFilters);

        Log::channel('updateGameResultEquipmentMainSummary')->info("Using versioned table: {$tableName}");

        // 테이블 존재 확인 및 생성
        $this->versionedTableManager->ensureGameResultEquipmentMainSummaryTableExists($tableName);

        $tiers = $this->tierRange;

        // TRUNCATE는 DDL이므로 트랜잭션 밖에서 실행 (암묵적 커밋 방지)
        Log::channel('updateGameResultEquipmentMainSummary')->info('Truncating table...');
        DB::table($tableName)->truncate();
        Log::channel('updateGameResultEquipmentMainSummary')->info("Truncated table {$tableName}");

        // 데이터 처리하면서 바로 insert
        $insertChunkSize = 500;
        $totalInserted = 0;
        $batchData = [];

        try {

            foreach ($tiers as $tier) {
                $minScore = $this->rankRangeService->getMinScore($tier['tier'], $tier['tierNumber'], $versionFilters) ?: 0;
                $minTier = $tier['tier'].$tier['tierNumber'];
                echo $tier['tier'] . $tier['tierNumber'] . ':' . $minScore . "\n";

                $startTime = microtime(true);

                // Mythic 등급 처리 (통합 메타스코어)
                $mythicResults = $this->gameResultService->getGameResultEquipmentMain([
                    'version_season' => $versionSeason,
                    'version_major' => $versionMajor,
                    'version_minor' => $versionMinor,
                    'min_tier' => $minTier,
                    'min_score' => $minScore,
                ]);

                // Legend 등급 처리 (장착부위별 메타스코어)
                $legendResults = $this->gameResultService->getGameResultLegendEquipmentMain([
                    'version_season' => $versionSeason,
                    'version_major' => $versionMajor,
                    'version_minor' => $versionMinor,
                    'min_tier' => $minTier,
                    'min_score' => $minScore,
                ]);

                // 두 결과 병합
                $gameResults = [
                    'data' => array_merge($mythicResults['data'] ?? [], $legendResults['data'] ?? []),
                ];

                $queryTime = round((microtime(true) - $startTime) * 1000, 2);
                Log::channel('updateGameResultEquipmentMainSummary')->info("Query time for {$minTier}: {$queryTime}ms (Mythic + Legend)");

                $gameResultsCursor = $gameResults['data'];

                foreach ($gameResultsCursor as $gameResult) {
                    $batchData[] = [
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
                        'updated_at' => now(),
                        'created_at' => now(),
                    ];

                    // 일정 크기마다 insert
                    if (count($batchData) >= $insertChunkSize) {
                        DB::table($tableName)->insert($batchData);
                        $totalInserted += count($batchData);
                        $batchData = [];

                        // 메모리 정리
                        if ($totalInserted % 5000 === 0) {
                            gc_collect_cycles();
                        }
                    }
                }

                // 티어별 처리 후 메모리 정리
                unset($gameResults, $gameResultsCursor);
                gc_collect_cycles();
            }

            // 남은 데이터 insert
            if (!empty($batchData)) {
                DB::table($tableName)->insert($batchData);
                $totalInserted += count($batchData);
            }

            Log::channel('updateGameResultEquipmentMainSummary')->info("Inserted {$totalInserted} new records");
            Log::channel('updateGameResultEquipmentMainSummary')->info('E: game equipment main result summary');
        } catch (\Exception $e) {
            Log::channel('updateGameResultEquipmentMainSummary')->error('Error: ' . $e->getMessage());
            Log::channel('updateGameResultEquipmentMainSummary')->error($e->getTraceAsString());
            throw $e;
        } finally {
            // 메모리 정리
            gc_collect_cycles();
        }
    }

    public function getList(array $filters)
    {
        $tableName = $this->getVersionedTableName($filters);

        // 테이블 존재 여부 확인
        if (!Schema::hasTable($tableName)) {
            return collect();
        }

        unset($filters['version_season'], $filters['version_major'], $filters['version_minor']);

        // 장비 페이지용: 랭킹 계산 제거로 성능 최적화
        $results = DB::table($tableName . ' as ges')
            ->select(
                'ges.*',
                'equipments.item_grade',
                'equipments.item_type2',
                'equipments.item_type3',
                // 장비 스탯 정보 추가
                'equipments.attack_power', 'equipments.attack_power_by_lv',
                'equipments.defense', 'equipments.defense_by_lv',
                'equipments.skill_amp', 'equipments.skill_amp_by_level',
                'equipments.skill_amp_ratio', 'equipments.skill_amp_ratio_by_level',
                'equipments.adaptive_force', 'equipments.adaptive_force_by_level',
                'equipments.max_hp', 'equipments.max_hp_by_lv',
                'equipments.hp_regen', 'equipments.hp_regen_ratio',
                'equipments.sp_regen', 'equipments.sp_regen_ratio',
                'equipments.attack_speed_ratio', 'equipments.attack_speed_ratio_by_lv',
                'equipments.critical_strike_chance', 'equipments.critical_strike_damage',
                'equipments.cooldown_reduction',
                'equipments.life_steal', 'equipments.normal_life_steal', 'equipments.skill_life_steal',
                'equipments.move_speed', 'equipments.move_speed_ratio', 'equipments.move_speed_out_of_combat',
                'equipments.penetration_defense', 'equipments.penetration_defense_ratio',
                // 고유 스탯 정보 추가
                'equipments.unique_attack_range',
                'equipments.unique_hp_healed_increase_ratio',
                'equipments.unique_cooldown_limit',
                'equipments.unique_tenacity',
                'equipments.unique_move_speed',
                'equipments.unique_penetration_defense',
                'equipments.unique_penetration_defense_ratio',
                'equipments.unique_life_steal',
                'equipments.unique_skill_amp_ratio'
            )
            ->join('equipments', 'equipments.id', '=', 'ges.equipment_id')
            ->where($filters)
            ->whereIn('equipments.item_grade', ['Legend','Mythic'])
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
