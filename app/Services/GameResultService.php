<?php

namespace App\Services;

use App\Models\GameResult;
use App\Models\GameResultEquipmentOrder;
use App\Models\GameResultFirstEquipmentOrder;
use App\Models\GameResultSkillOrder;
use App\Models\GameResultTraitOrder;
use App\Models\VersionHistory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;

class GameResultService
{
    private int $fetchGameUnitNumber;
    private int $searchGameNumber;

    protected RankRangeService $rankRangeService;

    public function __construct()
    {
        $this->fetchGameUnitNumber = config('erDev.fetchGameUnitNumber');
        $this->searchGameNumber = config('erDev.searchGameNumber');
        $this->rankRangeService = new RankRangeService();
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function storeGameResult($gameId)
    {
        $resultGameId = $gameId;
        for ($i = 1; $i < $this->fetchGameUnitNumber; $i++ ) {
            $resultGameId++;
            $data = $this->requestGameResult($resultGameId);
            // 게임데이터를 못찾으면 이전데이터로 되돌림
            if ($data['code'] == 404) {

                $hasGameId = false;
                $searchFirstGameId = $resultGameId;
                $tempGameId = $resultGameId;
                for($j= 1; $j <= $this->searchGameNumber; $j++) {
                    $tempGameId++;
                    $tempData = $this->requestGameResult($tempGameId);
                    if ($tempData['code'] == 200) {
                        $hasGameId = true;
                        $data = $tempData;
                        $resultGameId = $tempGameId;
                        break;
                    }

                }
                if (!$hasGameId) {
                    Log::channel('fetchGameResultData')->info($searchFirstGameId . ' game ID not found');
                    return $searchFirstGameId - 1;
                }
            }
            if ($data['code'] === 200 && in_array($data['userGames'][0]['matchingMode'], [3, 8]) && $data['userGames'][0]['matchingTeamMode'] === 3) {

                // 완전히 끝난게임이 아니면 삽입처리 취소 및 game id 시작값으로 초기화
                if (count($data['userGames']) <= 18) {
                    Log::channel('fetchGameResultData')->info($resultGameId . ' game ID not found');
                    return $gameId;
                }
                Log::channel('fetchGameResultData')->info('S: fetch game id : ' . $resultGameId);
                foreach ($data['userGames'] as $item) {
                    // 버전 히스토리 데이터 저장
                    $latestVersion = VersionHistory::latest('created_at')->first();
                    // 최신 버전+발생시간을 DB값과 비교하여 다를 경우만 저장
                    $newEndDate = Carbon::parse($item['startDtm'])->format('Y-m-d');
                    if (!$latestVersion
                        || $latestVersion->version_major !== ($item['versionMajor'] ?? null)
                        || $latestVersion->version_minor !== ($item['versionMinor'] ?? null)
                    ) {
                        // 버전 히스토리 기록
                        VersionHistory::create([
                            'version_season' => $item['versionSeason'] ?? null,
                            'version_major' => $item['versionMajor'] ?? null,
                            'version_minor' => $item['versionMinor'] ?? null,
                            'start_date' => $newEndDate,
                            'end_date' => $newEndDate,
                        ]);
                    } elseif ($latestVersion->end_at !== $newEndDate) {
                        VersionHistory::where('id', $latestVersion->id)->update(['end_date' => $newEndDate]);
                    }

                    $versionedGameTableManager = new VersionedGameTableManager();
                    $filters = [
                        'version_season' => $item['versionSeason'] ?? null,
                        'version_major' => $item['versionMajor'] ?? null,
                        'version_minor' => $item['versionMinor'] ?? null,
                    ];
                    $gameResultTableName = VersionedGameTableManager::getTableName('game_results', $filters);
                    $gameResultSkillOrderTableName = VersionedGameTableManager::getTableName('game_result_skill_orders', $filters);
                    $gameResultEquipmentOrderTableName = VersionedGameTableManager::getTableName('game_result_equipment_orders', $filters);
                    $gameResultFirstEquipmentOrderTableName = VersionedGameTableManager::getTableName('game_result_first_equipment_orders', $filters);
                    $gameResultTraitOrderTableName = VersionedGameTableManager::getTableName('game_result_trait_orders', $filters);

                    try {
                        $versionedGameTableManager->ensureGameResultTableExists($gameResultTableName);
                        $gameResult = GameResult::withTable($gameResultTableName)->create([
                            'game_id' => $resultGameId ?? null,
                            'user_id' => $item['userNum'] ?? null,
                            'mmr_before' => $item['mmrBefore'] ?? null,
                            'mmr_after' => $item['mmrAfter'] ?? null,
                            'mmr_gain' => $item['mmrGainInGame'] ?? null, // 입장료 제외 획득점수
                             // 'mmr_gain' => $item['mmrGain'] ?? null, // 입장료 포함 획득점수
                            'mmr_cost' => $item['mmrLossEntryCost'] ?? null,
                            'game_rank' => $item['gameRank'] ?? null,
                            'character_id' => $item['characterNum'] ?? null,
                            'weapon_id' => $item['equipFirstItemForLog'][0][0] ?? null,
                            'tactical_skill_id' => $item['tacticalSkillGroup'] ?? null,
                            'tactical_skill_level' => $item['tacticalSkillLevel'] ?? 0,
                            'player_kill_score' => $item['playerKill'] ?? null,
                            'player_death_score' => $item['playerDeaths'] ?? null,
                            'player_assist_score' => $item['playerAssistant'] ?? null,
                            'team_kill_score' => $item['teamKill'] ?? null,
                            'start_at' => Carbon::parse($item['startDtm'])->format('Y-m-d H:i:s'),
                            'version_season' => $item['versionSeason'] ?? null,
                            'version_major' => $item['versionMajor'] ?? null,
                            'version_minor' => $item['versionMinor'] ?? null,
                            // 유니온 전용 컬럼
                            'matching_mode' => $item['matchingMode'] ?? null,
                            'union_rank' => $item['squadRumbleRank'] ?? null,
                        ]);
                        if ($gameResult->id) {
                            // 스킬 찍은순서 기록
                            $orderLevel = 1;

                            $versionedGameTableManager->ensureGameResultSkillOrderTableExists($gameResultSkillOrderTableName);

                            foreach ($item['skillOrderInfo'] as $key => $item2) {
                                // 3000000이상 값은 무기스킬
                                if ($item2 < 3000000) {
                                    GameResultSkillOrder::withTable($gameResultSkillOrderTableName)->create([
                                        'game_result_id' => $gameResult->id ?? null,
                                        'skill_id' => $item2 ?? null,
                                        'order_level' => $orderLevel,
                                    ]);
                                    $orderLevel++;
                                }
                            }

                            // 최종 아이템
                            $versionedGameTableManager->ensureGameResultEquipmentOrderTableExists($gameResultEquipmentOrderTableName);
                            foreach ($item['equipment'] as $key => $item2) {
                                GameResultEquipmentOrder::withTable($gameResultEquipmentOrderTableName)->create([
                                    'game_result_id' => $gameResult->id ?? null,
                                    'equipment_id' => $item2 ?? null,
                                    'equipment_grade' => $item['equipmentGrade'][$key] ?? null,
                                    'order_quipment' => 0
                                ]);
                            }


                            // 최초장비 아이템
                            $versionedGameTableManager->ensureGameResultFirstEquipmentOrderTableExists($gameResultFirstEquipmentOrderTableName);
                            foreach ($item['equipFirstItemForLog'] as $key => $equipFirstItem) {
                                foreach($equipFirstItem as $item2) {
                                    GameResultFirstEquipmentOrder::withTable($gameResultFirstEquipmentOrderTableName)->create([
                                        'game_result_id' => $gameResult->id ?? null,
                                        'equipment_id' => $item2 ?? null
                                    ]);
                                }
                            }

                            // 선택한 특성
                            $versionedGameTableManager->ensureGameResultTraitOrderTableExists($gameResultTraitOrderTableName);
                            GameResultTraitOrder::withTable($gameResultTraitOrderTableName)->create([
                                'game_result_id' => $gameResult->id ?? null,
                                'trait_id' => $item['traitFirstCore'] ?? null,
                                'is_main' => true
                            ]);
                            foreach ($item['traitFirstSub'] as $item2) {
                                GameResultTraitOrder::withTable($gameResultTraitOrderTableName)->create([
                                    'game_result_id' => $gameResult->id ?? null,
                                    'trait_id' => $item2 ?? null,
                                    'is_main' => false
                                ]);
                            }
                            foreach ($item['traitSecondSub'] as $item2) {
                                GameResultTraitOrder::withTable($gameResultTraitOrderTableName)->create([
                                    'game_result_id' => $gameResult->id ?? null,
                                    'trait_id' => $item2 ?? null,
                                    'is_main' => false
                                ]);
                            }
                        }
                    } catch (QueryException $e) {
                        // 중복 & 에러 데이터 발생으로 조기종료
                        Log::channel('fetchGameResultData')->info('Error Message : ' . $e->getMessage());
                        Log::channel('fetchGameResultData')->info('E: Error game id : ' . $resultGameId);
                        return $resultGameId;
                    }
                }
                Log::channel('fetchGameResultData')->info('E: fetch game id : ' . $resultGameId);
            }
        }
        // 마지막으로 완료 처리된 게임id 리턴
        return $resultGameId;
    }

    public function store()
    {

    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function requestGameResult($resultGameId)
    {
        try {
            $client = new Client();
            $response = $client->get(
                "https://open-api.bser.io/v1/games/" . $resultGameId,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'x-api-key' => config('erDev.apiKey'),
                    ]
                ]
            );
            return json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            // API 키가 포함된 헤더 정보를 제거하고 로깅
            $safeMessage = preg_replace('/x-api-key["\s:]+[^"]+"/i', 'x-api-key: [REDACTED]', $e->getMessage());
            Log::channel('fetchGameResultData')->error('API request failed for game ID: ' . $resultGameId);
            Log::channel('fetchGameResultData')->error('Error: ' . $safeMessage);
            throw new \Exception('Failed to fetch game result for ID: ' . $resultGameId);
        }
    }


    public function getGameResultByGameRank(array $filters)
    {
        $gameResultTableName = VersionedGameTableManager::getTableName('game_results', $filters);
        $result = DB::table($gameResultTableName . ' as gr')
            ->leftJoin('equipments as e', 'gr.weapon_id', '=', 'e.id')
            ->leftJoin('characters as c', 'gr.character_id', '=', 'c.id')
            ->select(
                DB::raw('MAX(c.name) as name'), // ✅ `GROUP BY` 없이 가져오기
                DB::raw("CASE WHEN gr.character_id = 27 THEN 'All' ELSE e.item_type2 END AS weapon_type"),
                'gr.character_id',
                'gr.game_rank',
                DB::raw('COUNT(*) as game_rank_count'),
                DB::raw('AVG(gr.mmr_gain) as avg_mmr_gain'),
                DB::raw('SUM(CASE WHEN (gr.mmr_gain + gr.mmr_cost) > 0 THEN 1 ELSE 0 END) as positive_count'),
                DB::raw('SUM(CASE WHEN (gr.mmr_gain + gr.mmr_cost) < 0 THEN 1 ELSE 0 END) as negative_count'),
                DB::raw('IFNULL(AVG(CASE WHEN gr.mmr_gain + gr.mmr_cost > 0 THEN gr.mmr_gain END), 0) as positive_avg_mmr_gain'),
                DB::raw('IFNULL(AVG(CASE WHEN gr.mmr_gain + gr.mmr_cost < 0 THEN gr.mmr_gain END), 0) as negative_avg_mmr_gain'),
            )
            ->where('gr.matching_mode', 3) // 랭크모드만
            ->groupBy('gr.character_id',
                DB::raw("CASE WHEN gr.character_id = 27 THEN 'All' ELSE e.item_type2 END"),
                'gr.game_rank');

        if (isset($filters['version_major'])) {
            $result = $result->where('gr.version_major', $filters['version_major']);
        }
        if (isset($filters['version_minor'])) {
            $result = $result->where('gr.version_minor', $filters['version_minor']);
        }
        if (isset($filters['min_tier'])) {
            $result = $result->where('gr.mmr_before', '>=', $filters['min_score']);
        }
        return $result->get();
    }

    /**
     * 캐릭터별, 장비아이템별 정렬
     * @param array $filters
     * @return LazyCollection
     */
    public function getGameResultByEquipment(array $filters): LazyCollection
    {
        $gameResultTableName = VersionedGameTableManager::getTableName('game_results', $filters);
        $gameResultEquipmentOrderTableName = VersionedGameTableManager::getTableName('game_result_equipment_orders', $filters);
        $result = DB::table($gameResultEquipmentOrderTableName . ' as gre')
            ->join($gameResultTableName . ' as gr', 'gr.id', '=', 'gre.game_result_id')
            ->join('equipments as e', 'gr.weapon_id', '=', 'e.id')
            ->select(
                'gre.equipment_id',
                'gr.character_id',
                'gr.game_rank',
                DB::raw("CASE WHEN gr.character_id = 27 THEN 'All' ELSE e.item_type2 END AS weapon_type"),
                DB::raw('COUNT(*) as game_rank_count'),
                DB::raw('AVG(gr.mmr_gain) as avg_mmr_gain'),
                DB::raw('SUM(CASE WHEN (gr.mmr_gain + gr.mmr_cost) > 0 THEN 1 ELSE 0 END) as positive_count'),
                DB::raw('SUM(CASE WHEN (gr.mmr_gain + gr.mmr_cost) < 0 THEN 1 ELSE 0 END) as negative_count'),
                DB::raw('IFNULL(AVG(CASE WHEN gr.mmr_gain > 0 THEN gr.mmr_gain END), 0) as positive_avg_mmr_gain'),
                DB::raw('IFNULL(AVG(CASE WHEN gr.mmr_gain < 0 THEN gr.mmr_gain END), 0) as negative_avg_mmr_gain'),
            )
            ->where('gr.matching_mode', 3) // 랭크모드만
            ->groupBy(
                'gr.character_id',
                DB::raw("CASE WHEN gr.character_id = 27 THEN 'All' ELSE e.item_type2 END"),
                'gre.equipment_id',
                'gr.game_rank'
            );


        if (isset($filters['version_major'])) {
            $result = $result->where('gr.version_major', $filters['version_major']);
        }
        if (isset($filters['version_minor'])) {
            $result = $result->where('gr.version_minor', $filters['version_minor']);
        }
        if (isset($filters['min_tier'])) {
            $result = $result->where('gr.mmr_before', '>=', $filters['min_score']);
        }

        return $result->cursor(); // Use cursor() instead of get()
    }

    /**
     * 캐릭터별, 장비아이템별 정렬
     * @param array $filters
     * @return LazyCollection
     */
    public function getGameResultByTrait(array $filters): LazyCollection
    {
        $gameResultTableName = VersionedGameTableManager::getTableName('game_results', $filters);
        $gameResultTraitOrderTableName = VersionedGameTableManager::getTableName('game_result_trait_orders', $filters);
        $result = DB::table($gameResultTraitOrderTableName . ' as grt')
            ->join($gameResultTableName . ' as gr', 'gr.id', '=', 'grt.game_result_id')
            ->join('equipments as e', 'gr.weapon_id', '=', 'e.id')
            ->select(
                'grt.trait_id',
                'grt.is_main',
                'gr.character_id',
                'gr.game_rank',
                DB::raw("CASE WHEN gr.character_id = 27 THEN 'All' ELSE e.item_type2 END AS weapon_type"),
                DB::raw('COUNT(*) as game_rank_count'),
                DB::raw('AVG(gr.mmr_gain) as avg_mmr_gain'),
                DB::raw('SUM(CASE WHEN (gr.mmr_gain + gr.mmr_cost) > 0 THEN 1 ELSE 0 END) as positive_count'),
                DB::raw('SUM(CASE WHEN (gr.mmr_gain + gr.mmr_cost) < 0 THEN 1 ELSE 0 END) as negative_count'),
                DB::raw('IFNULL(AVG(CASE WHEN gr.mmr_gain > 0 THEN gr.mmr_gain END), 0) as positive_avg_mmr_gain'),
                DB::raw('IFNULL(AVG(CASE WHEN gr.mmr_gain < 0 THEN gr.mmr_gain END), 0) as negative_avg_mmr_gain'),
            )
            ->where('gr.matching_mode', 3) // 랭크모드만
            ->groupBy(
                'gr.character_id',
                DB::raw("CASE WHEN gr.character_id = 27 THEN 'All' ELSE e.item_type2 END"),
                'grt.trait_id',
                'grt.is_main',
                'gr.game_rank'
            );


        if (isset($filters['version_major'])) {
            $result = $result->where('gr.version_major', $filters['version_major']);
        }
        if (isset($filters['version_minor'])) {
            $result = $result->where('gr.version_minor', $filters['version_minor']);
        }
        if (isset($filters['min_tier'])) {
            $result = $result->where('gr.mmr_before', '>=', $filters['min_score']);
        }

        return $result->cursor(); // Use cursor() instead of get()
    }



    /**
     * 캐릭터별, 전술스킬 별 정렬
     * @param array $filters
     * @return LazyCollection
     */
    public function getGameResultByTacticalSkill(array $filters): LazyCollection
    {
        $gameResultTableName = VersionedGameTableManager::getTableName('game_results', $filters);
        $result = DB::table($gameResultTableName . ' as gr')
            ->join('equipments as e', 'gr.weapon_id', '=', 'e.id')
            ->select(
                'gr.tactical_skill_id',
                'gr.tactical_skill_level',
                'gr.character_id',
                'gr.game_rank',
                DB::raw("CASE WHEN gr.character_id = 27 THEN 'All' ELSE e.item_type2 END AS weapon_type"),
                DB::raw('COUNT(*) as game_rank_count'),
                DB::raw('AVG(gr.mmr_gain) as avg_mmr_gain'),
                DB::raw('SUM(CASE WHEN (gr.mmr_gain + gr.mmr_cost) > 0 THEN 1 ELSE 0 END) as positive_count'),
                DB::raw('SUM(CASE WHEN (gr.mmr_gain + gr.mmr_cost) < 0 THEN 1 ELSE 0 END) as negative_count'),
                DB::raw('IFNULL(AVG(CASE WHEN gr.mmr_gain > 0 THEN gr.mmr_gain END), 0) as positive_avg_mmr_gain'),
                DB::raw('IFNULL(AVG(CASE WHEN gr.mmr_gain < 0 THEN gr.mmr_gain END), 0) as negative_avg_mmr_gain'),
            )
            ->where('gr.matching_mode', 3) // 랭크모드만
            ->groupBy(
                'gr.character_id',
                DB::raw("CASE WHEN gr.character_id = 27 THEN 'All' ELSE e.item_type2 END"),
                'gr.tactical_skill_id',
                'gr.tactical_skill_level',
                'gr.game_rank'
            );


        if (isset($filters['version_major'])) {
            $result = $result->where('gr.version_major', $filters['version_major']);
        }
        if (isset($filters['version_minor'])) {
            $result = $result->where('gr.version_minor', $filters['version_minor']);
        }
        if (isset($filters['min_tier'])) {
            $result = $result->where('gr.mmr_before', '>=', $filters['min_score']);
        }

        return $result->cursor(); // Use cursor() instead of get()
    }
    /**
     * @param array $filters
     * @return array[
     *  'name' => string
     *  'weapon_type' => string
     *  'character_id' => int
     *  'top1_count' => int
     *  'top2_count' => int
     *  'top4_count' => int
     *  'game_count' => int
     *  'positive_count' => int
     *  'negative_count' => int
     *  'avg_mmr_gain' => int
     *  'avg_positive_mmr_gain' => int
     *  'avg_negative_mmr_gain' => int
     * ]
     */
    public function getGameResultMain(array $filters)
    {
        $gameResultTableName = VersionedGameTableManager::getTableName('game_results', $filters);
        $results = DB::table($gameResultTableName . ' as gr')
            ->leftJoin('equipments as e', 'gr.weapon_id', '=', 'e.id')
            ->leftJoin('characters as c', 'gr.character_id', '=', 'c.id')
            ->select(

                DB::raw('MAX(c.name) as name'), // ✅ `GROUP BY` 없이 가져오기
                DB::raw("CASE WHEN gr.character_id = 27 THEN 'All' ELSE e.item_type2 END AS weapon_type"),
                'gr.character_id',
                DB::raw('COUNT(*) as game_count'),
                DB::raw('SUM(CASE WHEN (gr.mmr_gain + gr.mmr_cost) > 0 THEN 1 ELSE 0 END) as positive_count'),
                DB::raw('SUM(CASE WHEN (gr.mmr_gain + gr.mmr_cost) < 0 THEN 1 ELSE 0 END) as negative_count'),
                DB::raw('AVG(gr.mmr_gain) as avg_mmr_gain'),
                DB::raw('AVG(CASE WHEN (gr.mmr_gain + gr.mmr_cost) > 0 THEN (gr.mmr_gain + gr.mmr_cost) END) as avg_positive_mmr_gain'),
                DB::raw('AVG(CASE WHEN (gr.mmr_gain + gr.mmr_cost) < 0 THEN (gr.mmr_gain + gr.mmr_cost) END) as avg_negative_mmr_gain'),
                DB::raw('SUM(CASE WHEN gr.game_rank <= 4 THEN 1 ELSE 0 END) AS top4_count'),
                DB::raw('SUM(CASE WHEN gr.game_rank <= 2 THEN 1 ELSE 0 END) AS top2_count'),
                DB::raw('SUM(CASE WHEN gr.game_rank = 1 THEN 1 ELSE 0 END) AS top1_count')
            )
            ->where('gr.matching_mode', 3) // 랭크모드만
            ->whereNotNull('e.item_type2')
            ->groupBy(
                'gr.character_id',
                DB::raw("CASE WHEN gr.character_id = 27 THEN 'All' ELSE e.item_type2 END")
            )
            ->orderBy('game_count', 'desc');

        if (isset($filters['version_major'])) {
            $results = $results->where('gr.version_major', $filters['version_major']);
        }
        if (isset($filters['version_minor'])) {
            $results = $results->where('gr.version_minor', $filters['version_minor']);
        }
        if (isset($filters['min_tier'])) {
            $results = $results->where('gr.mmr_before', '>=', $filters['min_score']);
        }
        $gameResults = $results->get();
        $total = array();
        $totalAll = 0;

        // 메타점수 계산용 변수
        $metaStandard = [
            'avgMmrGain' => 0,
        ];

        $data = [];
        foreach ($gameResults as $item) {
            $key = $item->name . '-' . $item->weapon_type;
            $data[$key] = [
                'characterId' => $item->character_id,
                'name' => $item->name,
                'weaponType' => $item->weapon_type,
                'gameCount' => $item->game_count,
                'positiveGameCount' => $item->positive_count,
                'negativeGameCount' => $item->negative_count,
                'avgMmrGain' => round($item->avg_mmr_gain,1),
                'top1Count' => $item->top1_count,
                'top2Count' => $item->top2_count,
                'top4Count' => $item->top4_count,
                'positiveAvgMmrGain' => round($item->avg_positive_mmr_gain,1),
                'negativeAvgMmrGain' => round($item->avg_negative_mmr_gain,1),
            ];
            if (!isset($total[$key])) {
                $total[$key] = 0;
            }
            $totalAll += $item->game_count;
            $total[$key] += $item->game_count;
            $metaStandard['avgMmrGain'] += $item->avg_mmr_gain;
        }
        if (count($data) == 0) {
            Log::channel('updateGameResultSummary')->info($filters['min_tier'] . ' : game result summary not found DATA');
            return [
                    'total' => [],
                    'data' => [],
            ];
        }
        $metaStandard['avgMmrGain'] = $metaStandard['avgMmrGain'] / count($data);
        $metaStandard['gameCount'] = $totalAll / count($data);
        $metaStandard['gameCountPercent'] = (1 / count($data)) * 100;
        $metaStandard['dataCount'] = count($data);
        foreach ($data as $name => $item) {
            $gameCountPercent = $item['gameCount'] ? round(($item['gameCount'] / $totalAll) * 100, 2) : 0;
            $positiveGameCountPercent = $item['gameCount'] ? round(($item['positiveGameCount'] / $item['gameCount']) * 100, 2) : 0;
            $negativeGameCountPercent = $item['gameCount'] ? round(($item['negativeGameCount'] / $item['gameCount']) * 100, 2) : 0;
            $top1CountPercent = $item['top1Count'] ? round(($item['top1Count'] / $total[$name]) * 100, 2) : 0;
            $top2CountPercent = $item['top2Count'] ? round(($item['top2Count'] / $total[$name]) * 100, 2) : 0;
            $top4CountPercent = $item['top4Count'] ? round(($item['top4Count'] / $total[$name]) * 100, 2) : 0;
            $endgameWinPercent = $item['top2Count'] ? round(($item['top1Count'] / $item['top2Count']) * 100, 2) : 0;
            $data[$name]['gameCountPercent'] = $gameCountPercent;
            $data[$name]['positiveGameCountPercent'] = $positiveGameCountPercent;
            $data[$name]['negativeGameCountPercent'] = $negativeGameCountPercent;
            $data[$name]['top1CountPercent'] = $top1CountPercent;
            $data[$name]['top2CountPercent'] = $top2CountPercent;
            $data[$name]['top4CountPercent'] = $top4CountPercent;
            $data[$name]['endgameWinPercent'] = $endgameWinPercent;
            $metaData = $this->getMetaDataNew($data[$name], $metaStandard);
            $data[$name]['metaScore'] = $metaData['metaScore'];
            $data[$name]['metaTier'] = $metaData['metaTier'];
        }
        $result = [
            'total' => $total,
            'data' => $data,
        ];
        return $result;
    }
    /**
     * @param array $filters
     * @return array[
     *  'name' => string
     *  'weapon_type' => string
     *  'character_id' => int
     *  'top1_count' => int
     *  'top2_count' => int
     *  'top4_count' => int
     *  'game_count' => int
     *  'positive_count' => int
     *  'negative_count' => int
     *  'avg_mmr_gain' => int
     *  'avg_positive_mmr_gain' => int
     *  'avg_negative_mmr_gain' => int
     * ]
     */
    public function getGameResultEquipmentMain(array $filters)
    {
        $gameResultTableName = VersionedGameTableManager::getTableName('game_results', $filters);
        $gameResultEquipmentOrderTableName = VersionedGameTableManager::getTableName('game_result_equipment_orders', $filters);
        $results = DB::table($gameResultEquipmentOrderTableName . ' as gre')
            ->join($gameResultTableName . ' as gr', 'gr.id', '=', 'gre.game_result_id')
            ->join('equipments as e', 'gre.equipment_id', '=', 'e.id')
            ->select(
                'gre.equipment_id',
                DB::raw('MAX(e.name) as name'), // ✅ `GROUP BY` 없이 가져오기
                DB::raw('COUNT(*) as game_count'),
                DB::raw('SUM(CASE WHEN (gr.mmr_gain + gr.mmr_cost) > 0 THEN 1 ELSE 0 END) as positive_count'),
                DB::raw('SUM(CASE WHEN (gr.mmr_gain + gr.mmr_cost) < 0 THEN 1 ELSE 0 END) as negative_count'),
                DB::raw('AVG(gr.mmr_gain) as avg_mmr_gain'),
                DB::raw('AVG(CASE WHEN (gr.mmr_gain + gr.mmr_cost) > 0 THEN (gr.mmr_gain + gr.mmr_cost) END) as avg_positive_mmr_gain'),
                DB::raw('AVG(CASE WHEN (gr.mmr_gain + gr.mmr_cost) < 0 THEN (gr.mmr_gain + gr.mmr_cost) END) as avg_negative_mmr_gain'),
                DB::raw('SUM(CASE WHEN gr.game_rank <= 4 THEN 1 ELSE 0 END) AS top4_count'),
                DB::raw('SUM(CASE WHEN gr.game_rank <= 2 THEN 1 ELSE 0 END) AS top2_count'),
                DB::raw('SUM(CASE WHEN gr.game_rank = 1 THEN 1 ELSE 0 END) AS top1_count')
            )
            ->where('gr.matching_mode', 3) // 랭크모드만
            ->whereNotNull('e.item_type2')
            ->whereNotIn('e.item_type1', ['Weapon'])
            ->whereIn('e.item_grade', ['Legend', 'Mythic'])
            ->groupBy(
                'gre.equipment_id',
            )
            ->orderBy('game_count', 'desc');

        if (isset($filters['version_major'])) {
            $results = $results->where('gr.version_major', $filters['version_major']);
        }
        if (isset($filters['version_minor'])) {
            $results = $results->where('gr.version_minor', $filters['version_minor']);
        }
        if (isset($filters['min_tier'])) {
            $results = $results->where('gr.mmr_before', '>=', $filters['min_score']);
        }
        $gameResults = $results->get();
        $total = array();
        $totalAll = 0;

        // 메타점수 계산용 변수
        $metaStandard = [
            'avgMmrGain' => 0,
        ];

        $data = [];
        foreach ($gameResults as $item) {
            $key = $item->equipment_id;
            $data[$key] = [
                'equipmentId' => $item->equipment_id,
                'name' => $item->name,
                'gameCount' => $item->game_count,
                'positiveGameCount' => $item->positive_count,
                'negativeGameCount' => $item->negative_count,
                'avgMmrGain' => round($item->avg_mmr_gain,1),
                'top1Count' => $item->top1_count,
                'top2Count' => $item->top2_count,
                'top4Count' => $item->top4_count,
                'positiveAvgMmrGain' => round($item->avg_positive_mmr_gain,1),
                'negativeAvgMmrGain' => round($item->avg_negative_mmr_gain,1),
            ];
            if (!isset($total[$key])) {
                $total[$key] = 0;
            }
            $totalAll += $item->game_count;
            $total[$key] += $item->game_count;
            $metaStandard['avgMmrGain'] += $item->avg_mmr_gain;
        }
        if (count($data) == 0) {
            Log::channel('updateGameResultEquipmentMainSummary')->info($filters['min_tier'] . ' : game result eqiupment main summary not found DATA');
            return [
                'total' => [],
                'data' => [],
            ];
        }
        $metaStandard['avgMmrGain'] = $metaStandard['avgMmrGain'] / count($data);
        $metaStandard['gameCount'] = $totalAll * 4 / count($data);
        $metaStandard['gameCountPercent'] = (4 / count($data)) * 100;
        $metaStandard['dataCount'] = count($data);
        foreach ($data as $name => $item) {
            $gameCountPercent = $item['gameCount'] ? round(($item['gameCount'] / $totalAll) * 100, 2) : 0;
            $positiveGameCountPercent = $item['gameCount'] ? round(($item['positiveGameCount'] / $item['gameCount']) * 100, 2) : 0;
            $negativeGameCountPercent = $item['gameCount'] ? round(($item['negativeGameCount'] / $item['gameCount']) * 100, 2) : 0;
            $top1CountPercent = $item['top1Count'] ? round(($item['top1Count'] / $total[$name]) * 100, 2) : 0;
            $top2CountPercent = $item['top2Count'] ? round(($item['top2Count'] / $total[$name]) * 100, 2) : 0;
            $top4CountPercent = $item['top4Count'] ? round(($item['top4Count'] / $total[$name]) * 100, 2) : 0;
            $endgameWinPercent = $item['top2Count'] ? round(($item['top1Count'] / $item['top2Count']) * 100, 2) : 0;
            $data[$name]['gameCountPercent'] = $gameCountPercent * 4;
            $data[$name]['positiveGameCountPercent'] = $positiveGameCountPercent;
            $data[$name]['negativeGameCountPercent'] = $negativeGameCountPercent;
            $data[$name]['top1CountPercent'] = $top1CountPercent;
            $data[$name]['top2CountPercent'] = $top2CountPercent;
            $data[$name]['top4CountPercent'] = $top4CountPercent;
            $data[$name]['endgameWinPercent'] = $endgameWinPercent;
            $metaData = $this->getEquipmentMetaDataNew($data[$name], $metaStandard);
            $data[$name]['metaScore'] = $metaData['metaScore'];
            $data[$name]['metaTier'] = $metaData['metaTier'];
        }
        $result = [
            'total' => $total,
            'data' => $data,
        ];
        return $result;
    }
    /**
     * @param array $filters
     * @return array[
     *  'name' => string
     *  'weapon_type' => string
     *  'character_id' => int
     *  'top1_count' => int
     *  'top2_count' => int
     *  'top4_count' => int
     *  'game_count' => int
     *  'positive_count' => int
     *  'negative_count' => int
     *  'avg_mmr_gain' => int
     *  'avg_positive_mmr_gain' => int
     *  'avg_negative_mmr_gain' => int
     * ]
     */
    public function getGameResultFirstEquipmentMain(array $filters)
    {
        $gameResultTableName = VersionedGameTableManager::getTableName('game_results', $filters);
        $gameResultFirstEquipmentOrderTableName = VersionedGameTableManager::getTableName('game_result_first_equipment_orders', $filters);
        $results = DB::table($gameResultFirstEquipmentOrderTableName . ' as gre')
            ->join($gameResultTableName . ' as gr', 'gr.id', '=', 'gre.game_result_id')
            ->join('equipments as e', 'gre.equipment_id', '=', 'e.id')
            ->select(
                'gre.equipment_id',
                DB::raw('MAX(e.name) as name'), // ✅ `GROUP BY` 없이 가져오기
                DB::raw('COUNT(*) as game_count'),
                DB::raw('SUM(CASE WHEN (gr.mmr_gain + gr.mmr_cost) > 0 THEN 1 ELSE 0 END) as positive_count'),
                DB::raw('SUM(CASE WHEN (gr.mmr_gain + gr.mmr_cost) < 0 THEN 1 ELSE 0 END) as negative_count'),
                DB::raw('AVG(gr.mmr_gain) as avg_mmr_gain'),
                DB::raw('AVG(CASE WHEN (gr.mmr_gain + gr.mmr_cost) > 0 THEN (gr.mmr_gain + gr.mmr_cost) END) as avg_positive_mmr_gain'),
                DB::raw('AVG(CASE WHEN (gr.mmr_gain + gr.mmr_cost) < 0 THEN (gr.mmr_gain + gr.mmr_cost) END) as avg_negative_mmr_gain'),
                DB::raw('SUM(CASE WHEN gr.game_rank <= 4 THEN 1 ELSE 0 END) AS top4_count'),
                DB::raw('SUM(CASE WHEN gr.game_rank <= 2 THEN 1 ELSE 0 END) AS top2_count'),
                DB::raw('SUM(CASE WHEN gr.game_rank = 1 THEN 1 ELSE 0 END) AS top1_count')
            )
            ->where('gr.matching_mode', 3) // 랭크모드만
            ->whereNotNull('e.item_type2')
            ->whereNotIn('e.item_type1', ['Weapon'])
            ->whereIn('e.item_grade', ['Epic'])
            ->groupBy(
                'gre.equipment_id',
            )
            ->orderBy('game_count', 'desc');

        if (isset($filters['version_major'])) {
            $results = $results->where('gr.version_major', $filters['version_major']);
        }
        if (isset($filters['version_minor'])) {
            $results = $results->where('gr.version_minor', $filters['version_minor']);
        }
        if (isset($filters['min_tier'])) {
            $results = $results->where('gr.mmr_before', '>=', $filters['min_score']);
        }
        $gameResults = $results->get();
        $total = array();
        $totalAll = 0;

        // 메타점수 계산용 변수
        $metaStandard = [
            'avgMmrGain' => 0,
        ];

        $data = [];
        foreach ($gameResults as $item) {
            $key = $item->equipment_id;
            $data[$key] = [
                'equipmentId' => $item->equipment_id,
                'name' => $item->name,
                'gameCount' => $item->game_count,
                'positiveGameCount' => $item->positive_count,
                'negativeGameCount' => $item->negative_count,
                'avgMmrGain' => round($item->avg_mmr_gain,1),
                'top1Count' => $item->top1_count,
                'top2Count' => $item->top2_count,
                'top4Count' => $item->top4_count,
                'positiveAvgMmrGain' => round($item->avg_positive_mmr_gain,1),
                'negativeAvgMmrGain' => round($item->avg_negative_mmr_gain,1),
            ];
            if (!isset($total[$key])) {
                $total[$key] = 0;
            }
            $totalAll += $item->game_count;
            $total[$key] += $item->game_count;
            $metaStandard['avgMmrGain'] += $item->avg_mmr_gain;
        }
        if (count($data) == 0) {
            Log::channel('updateGameResultEquipmentMainSummary')->info($filters['min_tier'] . ' : game result eqiupment main summary not found DATA');
            return [
                'total' => [],
                'data' => [],
            ];
        }
        $metaStandard['avgMmrGain'] = $metaStandard['avgMmrGain'] / count($data);
        $metaStandard['gameCount'] = $totalAll * 4 / count($data);
        $metaStandard['gameCountPercent'] = (4 / count($data)) * 100;
        $metaStandard['dataCount'] = count($data);
        foreach ($data as $name => $item) {
            $gameCountPercent = $item['gameCount'] ? round(($item['gameCount'] / $totalAll) * 100, 2) : 0;
            $positiveGameCountPercent = $item['gameCount'] ? round(($item['positiveGameCount'] / $item['gameCount']) * 100, 2) : 0;
            $negativeGameCountPercent = $item['gameCount'] ? round(($item['negativeGameCount'] / $item['gameCount']) * 100, 2) : 0;
            $top1CountPercent = $item['top1Count'] ? round(($item['top1Count'] / $total[$name]) * 100, 2) : 0;
            $top2CountPercent = $item['top2Count'] ? round(($item['top2Count'] / $total[$name]) * 100, 2) : 0;
            $top4CountPercent = $item['top4Count'] ? round(($item['top4Count'] / $total[$name]) * 100, 2) : 0;
            $endgameWinPercent = $item['top2Count'] ? round(($item['top1Count'] / $item['top2Count']) * 100, 2) : 0;
            $data[$name]['gameCountPercent'] = $gameCountPercent * 4;
            $data[$name]['positiveGameCountPercent'] = $positiveGameCountPercent;
            $data[$name]['negativeGameCountPercent'] = $negativeGameCountPercent;
            $data[$name]['top1CountPercent'] = $top1CountPercent;
            $data[$name]['top2CountPercent'] = $top2CountPercent;
            $data[$name]['top4CountPercent'] = $top4CountPercent;
            $data[$name]['endgameWinPercent'] = $endgameWinPercent;
            $metaData = $this->getEquipmentMetaDataNew($data[$name], $metaStandard);
            $data[$name]['metaScore'] = $metaData['metaScore'];
            $data[$name]['metaTier'] = $metaData['metaTier'];
        }
        $result = [
            'total' => $total,
            'data' => $data,
        ];
        return $result;
    }


    private function getMetaDataNew(array $data, array $metaStandard): array
    {
        // 7팀:8팀 = 3:7 가중 평균
        $rankRatio = (7 * 0.3 + 8 * 0.7) / 2;

        // 퍼센트(0~100)를 기준 50과 비교하여 로그 편차 계산
        $logDelta = function (float $percent, float $scale = 50): float {
            $delta = $percent - $scale;
            return $delta < 0
                ? -log(1 + abs($delta))
                : log(1 + $delta);
        };

        // Top1/2/4: 순위 점수 편차 보정
        $top1Score = $logDelta($data['top1CountPercent'] * $rankRatio);
        $top2Score = $logDelta(($data['top2CountPercent'] * $rankRatio / 2));
        $top4Score = $logDelta(($data['top4CountPercent'] * $rankRatio / 4));

        // Clutch율: Top2 대비 Top1의 비율 (결승 퍼포먼스)
        $clutchRate = ($data['top2CountPercent'] > 0)
            ? ($data['top1CountPercent'] / $data['top2CountPercent']) * 100
            : 0;
        $endGameScore = $logDelta($clutchRate);

        // 평균 점수 (MMR gain) → 메타 기준과의 상대 보정
        $mmrDelta = $data['avgMmrGain'] - $metaStandard['avgMmrGain'];
        $mmrScore = $mmrDelta < 0
            ? -log(1 + abs($mmrDelta))
            : log(1 + $mmrDelta);

        // 픽률 (0~100) → 기준 대비 상대 편차
        $pickDelta = $data['gameCountPercent'] - $metaStandard['gameCountPercent'];
        $pickScore = $pickDelta < 0
            ? -log(1 + abs($pickDelta))
            : log(1 + $pickDelta);

        // 안정성 계수: 극저픽 캐릭터의 성능 감쇠 (신뢰도)
        $pickRate = max($data['gameCountPercent'] / 100, 0.001); // 최소 0.1%
        $stabilityFactor = log(1 + $pickRate) / log(1 + 0.05);   // 5% 이상이면 1.0
        // $stabilityFactor = min($stabilityFactor, 1.0);

        // 픽률 기반 전체 스코어 가중치
        $pickWeight = log(1 + max($pickRate, 0.01)) ** 0.8;

        // P: 성능 점수
        $P = (
                $endGameScore * 0.15 +
                $top2Score * 0.2 +
                $top4Score * 0.2 +
                $mmrScore * 2.8
            ) * $stabilityFactor;

        // A: 신뢰도 보정 + 픽률 점수
        $A = $pickScore * 3;

        // 최종 메타 점수
        $metaScore = ($P * 1.5 + $A) * (0.6 + 0.4 * $pickWeight) * 2;

        // 티어 분류
        $metaTier = match (true) {
            $metaScore >= 5 => 'OP',
            $metaScore >= 3 => '1',
            $metaScore >= 2 => '2',
            $metaScore >= -1 => '3',
            $metaScore >= -2 => '4',
            $metaScore >= -4 => '5',
            default => 'RIP',
        };

        return [
            'metaTier' => $metaTier,
            'metaScore' => $metaScore,
        ];
    }

    private function getEquipmentMetaDataNew(array $data, array $metaStandard): array
    {
        // 7팀:8팀 = 3:7 가중 평균
        $rankRatio = (7 * 0.3 + 8 * 0.7) / 2;

        // 퍼센트(0~100)를 기준 50과 비교하여 로그 편차 계산
        $logDelta = function (float $percent, float $scale = 50): float {
            $delta = $percent - $scale;
            return $delta < 0
                ? -log(1 + abs($delta))
                : log(1 + $delta);
        };

        // Top1/2/4: 순위 점수 편차 보정
        $top1Score = $logDelta($data['top1CountPercent'] * $rankRatio);
        $top2Score = $logDelta(($data['top2CountPercent'] * $rankRatio / 2));
        $top4Score = $logDelta(($data['top4CountPercent'] * $rankRatio / 4));

        // Clutch율: Top2 대비 Top1의 비율 (결승 퍼포먼스)
        $clutchRate = ($data['top2CountPercent'] > 0)
            ? ($data['top1CountPercent'] / $data['top2CountPercent']) * 100
            : 0;
        $endGameScore = $logDelta($clutchRate);

        // 평균 점수 (MMR gain) → 메타 기준과의 상대 보정
        $mmrDelta = $data['avgMmrGain'] - $metaStandard['avgMmrGain'];
        $mmrScore = $mmrDelta < 0
            ? -log(1 + abs($mmrDelta))
            : log(1 + $mmrDelta);

        // 픽률 (0~100) → 기준 대비 상대 편차
        $pickDelta = $data['gameCountPercent'] - $metaStandard['gameCountPercent'];
        $pickScore = $pickDelta < 0
            ? -log(1 + abs($pickDelta))
            : log(1 + $pickDelta);

        // 안정성 계수: 극저픽 캐릭터의 성능 감쇠 (신뢰도)
        $pickRate = max($data['gameCountPercent'] / 5 / 100, 0.001); // 최소 0.1%
        $stabilityFactor = log(1 + $pickRate) / log(1 + 0.05);   // 5% 이상이면 1.0
        // $stabilityFactor = min($stabilityFactor, 1.0);

        // 픽률 기반 전체 스코어 가중치
        $pickWeight = log(1 + max($pickRate, 0.01)) ** 0.8;

        // P: 성능 점수
        $P = (
                $endGameScore * 0.2 +
                $top2Score * 0.25 +
                $top4Score * 0.25 +
                $mmrScore * 3.2
            ) * $stabilityFactor;

        // A: 신뢰도 보정 + 픽률 점수
        $A = $pickScore * 2.5;

        // 최종 메타 점수
        $metaScore = ($P * 1.5 + $A) * (0.6 + 0.4 * $pickWeight) * 2;

        // 티어 분류
        $metaTier = match (true) {
            $metaScore >= 5 => 'OP',
            $metaScore >= 3 => '1',
            $metaScore >= 1 => '2',
            $metaScore >= -1 => '3',
            $metaScore >= -3 => '4',
            $metaScore >= -5 => '5',
            default => 'RIP',
        };

        return [
            'metaTier' => $metaTier,
            'metaScore' => $metaScore,
        ];
    }
}
