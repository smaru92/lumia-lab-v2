<?php

namespace App\Services;

use App\Models\GameResult;
use App\Models\GameResultEquipmentOrder;
use App\Models\GameResultFirstEquipmentOrder;
use App\Models\GameResultSkillOrder;
use App\Models\GameResultTraitOrder;
use App\Models\VersionHistory;
use App\Services\VersionedGameTableManager;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise;
use GuzzleHttp\Exception\RequestException;
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
     * weapon_type을 결정하는 SQL CASE 문을 생성합니다.
     * @return string
     */
    private function getWeaponTypeCaseStatement(): string
    {
        $weaponTypeMapping = config('erDev.characterWeaponTypeMapping', []);

        $caseParts = ["CASE"];
        $caseParts[] = "WHEN gr.character_id = 27 THEN 'All'";

        // 각 캐릭터별 무기 분류 로직 추가
        foreach ($weaponTypeMapping as $characterId => $weaponTypes) {
            $caseParts[] = "WHEN gr.character_id = {$characterId} THEN";
            $caseParts[] = "CASE";

            foreach ($weaponTypes as $weaponTypeName => $weaponIds) {
                $weaponIdsStr = implode(', ', $weaponIds);
                $caseParts[] = "WHEN gr.weapon_id IN ({$weaponIdsStr}) THEN '{$weaponTypeName}'";
            }

            $caseParts[] = "ELSE e.item_type2";
            $caseParts[] = "END";
        }

        $caseParts[] = "ELSE e.item_type2";
        $caseParts[] = "END";

        return implode("\n                    ", $caseParts);
    }

    /**
     * 병렬로 여러 게임 결과를 요청 (배치 크기 10개)
     * @param array $gameIds
     * @return array
     */
    private function requestGameResultsParallel(array $gameIds): array
    {
        $client = new Client();
        $promises = [];

        foreach ($gameIds as $gameId) {
            $promises[$gameId] = $client->getAsync(
                "https://open-api.bser.io/v1/games/" . $gameId,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'x-api-key' => config('erDev.apiKey'),
                    ],
                    'timeout' => 5,
                    'connect_timeout' => 3,
                ]
            );
        }

        $results = [];
        $responses = Promise\Utils::settle($promises)->wait();

        foreach ($responses as $gameId => $response) {
            if ($response['state'] === 'fulfilled') {
                try {
                    $results[$gameId] = json_decode($response['value']->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    $results[$gameId] = ['code' => 500, 'message' => 'JSON decode error'];
                }
            } else {
                // 실패한 경우 (404 등)
                $results[$gameId] = ['code' => 404, 'message' => 'Not found'];
            }
        }

        return $results;
    }

    /**
     * @throws GuzzleException
     * @throws \JsonException
     */
    public function storeGameResult($gameId)
    {
        $resultGameId = $gameId;
        $firstSavedGameId = null; // 첫 번째 저장된 게임 ID
        $batchSize = 10; // 배치 크기 (e2-medium 스펙 고려)
        $batchNumber = 0; // 배치 번호

        Log::channel('fetchGameResultData')->info('=== Batch Processing Start ===');

        for ($i = 1; $i <= $this->fetchGameUnitNumber; $i += $batchSize) {
            $batchNumber++;
            $batchStartTime = microtime(true);

            // 배치로 처리할 게임 ID 배열 생성
            $gameIdsToFetch = [];
            $batchCount = min($batchSize, $this->fetchGameUnitNumber - $i + 1);

            for ($j = 0; $j < $batchCount; $j++) {
                $gameIdsToFetch[] = $gameId + $i + $j;
            }

            $batchStartId = $gameIdsToFetch[0];
            $batchEndId = $gameIdsToFetch[count($gameIdsToFetch) - 1];

            Log::channel('fetchGameResultData')->info("Batch #{$batchNumber} Start - Game IDs: {$batchStartId} ~ {$batchEndId}");

            // 병렬로 API 요청
            $batchResults = $this->requestGameResultsParallel($gameIdsToFetch);

            // 먼저 배치 내 미완료 게임이 있는지 찾기
            $stopAtGameId = null;
            foreach ($gameIdsToFetch as $checkGameId) {
                $checkData = $batchResults[$checkGameId] ?? ['code' => 404];

                if ($checkData['code'] === 200 &&
                    isset($checkData['userGames']) &&
                    in_array($checkData['userGames'][0]['matchingMode'], [3, 8]) &&
                    $checkData['userGames'][0]['matchingTeamMode'] === 3) {

                    // gameRank = 1 (1등)이 있는지 또는 gameRank = 2인 플레이어가 6명 이상인지 확인
                    $hasWinner = false;
                    $rank2Count = 0;

                    foreach ($checkData['userGames'] as $player) {
                        if (isset($player['gameRank'])) {
                            if ($player['gameRank'] == 1) {
                                $hasWinner = true;
                                break;
                            } elseif ($player['gameRank'] == 2) {
                                $rank2Count++;
                            }
                        }
                    }

                    // 1등이 있거나 2등이 6명 이상이면 완료된 게임으로 간주
                    if (!$hasWinner && $rank2Count < 6) {
                        Log::channel('fetchGameResultData')->info($checkGameId . ' game not finished (no rank 1 and rank 2 count: ' . $rank2Count . ') - will stop after saving previous games');
                        $stopAtGameId = $checkGameId;
                        break; // 더 이상 체크하지 않음
                    }
                }
            }

            // 배치의 첫 번째 게임이 404인 경우, 다음 게임들을 탐색
            $firstBatchGameId = $gameIdsToFetch[0];
            if (($batchResults[$firstBatchGameId]['code'] ?? 404) == 404 && $batchNumber == 1) {
                // 첫 번째 배치의 첫 게임이 404면 추가 탐색
                $hasGameId = false;
                $searchFirstGameId = $firstBatchGameId;
                $tempGameId = $firstBatchGameId;

                for($k = 1; $k <= $this->searchGameNumber; $k++) {
                    $tempGameId++;
                    $tempData = $this->requestGameResult($tempGameId);
                    if ($tempData['code'] == 200) {
                        $hasGameId = true;
                        // 찾은 게임 ID로 배치를 다시 구성
                        $gameIdsToFetch = [];
                        for ($j = 0; $j < $batchCount; $j++) {
                            $gameIdsToFetch[] = $tempGameId + $j;
                        }
                        // 새로운 배치로 병렬 요청
                        $batchResults = $this->requestGameResultsParallel($gameIdsToFetch);
                        break;
                    }
                }

                if (!$hasGameId) {
                    Log::channel('fetchGameResultData')->info($searchFirstGameId . ' game ID not found');
                    return $searchFirstGameId - 1;
                }
            }

            // 각 결과를 순차적으로 처리 (미완료 게임 이전까지만)
            foreach ($gameIdsToFetch as $currentGameId) {
                // 미완료 게임에 도달하면 해당 게임 이후 데이터 삭제 후 중단
                if ($stopAtGameId !== null && $currentGameId >= $stopAtGameId) {
                    Log::channel('fetchGameResultData')->info("Deleting game data from game_id >= {$stopAtGameId}");

                    // 미완료 게임 이후 데이터 삭제
                    $this->deleteGameResultsFrom($stopAtGameId);

                    $batchEndTime = microtime(true);
                    $batchDuration = round(($batchEndTime - $batchStartTime) * 1000, 2);
                    Log::channel('fetchGameResultData')->info("Batch #{$batchNumber} Stopped - Duration: {$batchDuration}ms");
                    Log::channel('fetchGameResultData')->info('=== Batch Processing Stopped ===');

                    // 마지막 저장된 게임 ID 로그
                    if ($firstSavedGameId !== null) {
                        Log::channel('fetchGameResultData')->info('E: fetch game id : ' . ($stopAtGameId - 1));
                    }

                    return $stopAtGameId - 1;
                }

                $resultGameId = $currentGameId;
                $data = $batchResults[$currentGameId] ?? ['code' => 404];

                // 게임데이터를 못찾으면 다음 게임 탐색 (첫 배치가 아닌 경우)
                if ($data['code'] == 404) {
                    $hasGameId = false;
                    $searchFirstGameId = $resultGameId;
                    $tempGameId = $resultGameId;

                    for($k = 1; $k <= $this->searchGameNumber; $k++) {
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
                    // 여기까지 왔다면 배치 체크에서 이미 완료된 게임임이 확인됨

                    // 첫 번째 저장 시에만 로그 기록
                    if ($firstSavedGameId === null) {
                        $firstSavedGameId = $resultGameId;
                        Log::channel('fetchGameResultData')->info('S: fetch game id : ' . $resultGameId);
                    }

                    // 첫 번째 플레이어 데이터로 버전 정보 확인 (모든 플레이어가 같은 버전)
                    $firstPlayer = $data['userGames'][0];

                    // 버전 히스토리 데이터 저장
                    $latestVersion = VersionHistory::latest('created_at')->first();
                    $newEndDate = Carbon::parse($firstPlayer['startDtm'])->format('Y-m-d');
                    if (!$latestVersion
                        || $latestVersion->version_major !== ($firstPlayer['versionMajor'] ?? null)
                        || $latestVersion->version_minor !== ($firstPlayer['versionMinor'] ?? null)
                    ) {
                        // 버전 히스토리 기록
                        VersionHistory::create([
                            'version_season' => $firstPlayer['versionSeason'] ?? null,
                            'version_major' => $firstPlayer['versionMajor'] ?? null,
                            'version_minor' => $firstPlayer['versionMinor'] ?? null,
                            'start_date' => $newEndDate,
                            'end_date' => $newEndDate,
                        ]);
                    } elseif ($latestVersion->end_date !== $newEndDate) {
                        VersionHistory::where('id', $latestVersion->id)->update(['end_date' => $newEndDate]);
                    }

                    $versionedGameTableManager = new VersionedGameTableManager();
                    $filters = [
                        'version_season' => $firstPlayer['versionSeason'] ?? null,
                        'version_major' => $firstPlayer['versionMajor'] ?? null,
                        'version_minor' => $firstPlayer['versionMinor'] ?? null,
                    ];
                    $gameResultTableName = VersionedGameTableManager::getTableName('game_results', $filters);
                    $gameResultSkillOrderTableName = VersionedGameTableManager::getTableName('game_result_skill_orders', $filters);
                    $gameResultEquipmentOrderTableName = VersionedGameTableManager::getTableName('game_result_equipment_orders', $filters);
                    $gameResultFirstEquipmentOrderTableName = VersionedGameTableManager::getTableName('game_result_first_equipment_orders', $filters);
                    $gameResultTraitOrderTableName = VersionedGameTableManager::getTableName('game_result_trait_orders', $filters);

                    try {
                        // 테이블 존재 확인
                        $versionedGameTableManager->ensureGameResultTableExists($gameResultTableName);
                        $versionedGameTableManager->ensureGameResultSkillOrderTableExists($gameResultSkillOrderTableName);
                        $versionedGameTableManager->ensureGameResultEquipmentOrderTableExists($gameResultEquipmentOrderTableName);
                        $versionedGameTableManager->ensureGameResultFirstEquipmentOrderTableExists($gameResultFirstEquipmentOrderTableName);
                        $versionedGameTableManager->ensureGameResultTraitOrderTableExists($gameResultTraitOrderTableName);

                        // 중복 체크: 이미 존재하는 game_id인지 확인
                        $existingGameData = DB::table($gameResultTableName)
                            ->where('game_id', $resultGameId)
                            ->exists();

                        if ($existingGameData) {
                            Log::channel('fetchGameResultData')->info('Duplicate game_id: ' . $resultGameId . ' - skipping');
                            continue; // 이미 존재하면 건너뛰기
                        }

                        // Bulk Insert를 위한 데이터 배열 준비
                        $gameResults = [];
                        $skillOrders = [];
                        $equipmentOrders = [];
                        $firstEquipmentOrders = [];
                        $traitOrders = [];

                        // 트랜잭션 시작
                        DB::beginTransaction();

                        foreach ($data['userGames'] as $item) {
                            $gameResults[] = [
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
                                'created_at' => now(),
                            ];
                        }

                        // GameResult Bulk Insert
                        DB::table($gameResultTableName)->insert($gameResults);

                        // 방금 삽입한 게임 결과들의 ID를 가져오기 (game_id와 user_id 조합으로 조회)
                        $insertedGameResults = DB::table($gameResultTableName)
                            ->where('game_id', $resultGameId)
                            ->get()
                            ->keyBy('user_id');

                        // 각 플레이어의 상세 데이터 수집
                        foreach ($data['userGames'] as $item) {
                            $gameResultId = $insertedGameResults[$item['userNum']]->id ?? null;

                            if (!$gameResultId) {
                                continue;
                            }

                            // 스킬 찍은순서 기록
                            $orderLevel = 1;
                            foreach ($item['skillOrderInfo'] as $skillId) {
                                // 3000000이상 값은 무기스킬
                                if ($skillId < 3000000) {
                                    $skillOrders[] = [
                                        'game_result_id' => $gameResultId,
                                        'skill_id' => $skillId ?? null,
                                        'order_level' => $orderLevel,
                                        'created_at' => now(),
                                    ];
                                    $orderLevel++;
                                }
                            }

                            // 최종 아이템
                            foreach ($item['equipment'] as $key => $equipmentId) {
                                $equipmentOrders[] = [
                                    'game_result_id' => $gameResultId,
                                    'equipment_id' => $equipmentId ?? null,
                                    'equipment_grade' => $item['equipmentGrade'][$key] ?? null,
                                    'order_quipment' => 0,
                                    'created_at' => now(),
                                ];
                            }

                            // 최초장비 아이템
                            foreach ($item['equipFirstItemForLog'] as $equipFirstItem) {
                                foreach ($equipFirstItem as $equipmentId) {
                                    $firstEquipmentOrders[] = [
                                        'game_result_id' => $gameResultId,
                                        'equipment_id' => $equipmentId ?? null,
                                        'created_at' => now(),
                                    ];
                                }
                            }

                            // 선택한 특성
                            $traitOrders[] = [
                                'game_result_id' => $gameResultId,
                                'trait_id' => $item['traitFirstCore'] ?? null,
                                'is_main' => true,
                                'created_at' => now(),
                            ];
                            foreach ($item['traitFirstSub'] as $traitId) {
                                $traitOrders[] = [
                                    'game_result_id' => $gameResultId,
                                    'trait_id' => $traitId ?? null,
                                    'is_main' => false,
                                    'created_at' => now(),
                                ];
                            }
                            foreach ($item['traitSecondSub'] as $traitId) {
                                $traitOrders[] = [
                                    'game_result_id' => $gameResultId,
                                    'trait_id' => $traitId ?? null,
                                    'is_main' => false,
                                    'created_at' => now(),
                                ];
                            }
                        }

                        // 모든 관련 데이터를 Bulk Insert
                        if (!empty($skillOrders)) {
                            DB::table($gameResultSkillOrderTableName)->insert($skillOrders);
                        }
                        if (!empty($equipmentOrders)) {
                            DB::table($gameResultEquipmentOrderTableName)->insert($equipmentOrders);
                        }
                        if (!empty($firstEquipmentOrders)) {
                            DB::table($gameResultFirstEquipmentOrderTableName)->insert($firstEquipmentOrders);
                        }
                        if (!empty($traitOrders)) {
                            DB::table($gameResultTraitOrderTableName)->insert($traitOrders);
                        }

                        // 트랜잭션 커밋
                        DB::commit();

                    } catch (QueryException $e) {
                        // 트랜잭션 롤백
                        DB::rollBack();
                        // 중복 & 에러 데이터 발생으로 조기종료
                        Log::channel('fetchGameResultData')->info('Error Message : ' . $e->getMessage());
                        Log::channel('fetchGameResultData')->info('E: Error game id : ' . $resultGameId);

                        $batchEndTime = microtime(true);
                        $batchDuration = round(($batchEndTime - $batchStartTime) * 1000, 2);
                        Log::channel('fetchGameResultData')->info("Batch #{$batchNumber} Failed - Duration: {$batchDuration}ms");

                        return $resultGameId;
                    }
                }
            }

            // 배치 처리 완료 로그
            $batchEndTime = microtime(true);
            $batchDuration = round(($batchEndTime - $batchStartTime) * 1000, 2);
            Log::channel('fetchGameResultData')->info("Batch #{$batchNumber} End - Duration: {$batchDuration}ms");
        }

        Log::channel('fetchGameResultData')->info('=== Batch Processing Complete ===');

        // 마지막 저장된 게임 ID 로그 기록
        if ($firstSavedGameId !== null) {
            Log::channel('fetchGameResultData')->info('E: fetch game id : ' . $resultGameId);
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
        $weaponTypeCaseStmt = $this->getWeaponTypeCaseStatement();

        $result = DB::table($gameResultTableName . ' as gr')
            ->leftJoin('equipments as e', 'gr.weapon_id', '=', 'e.id')
            ->leftJoin('characters as c', 'gr.character_id', '=', 'c.id')
            ->select(
                DB::raw('MAX(c.name) as name'), // ✅ `GROUP BY` 없이 가져오기
                DB::raw("{$weaponTypeCaseStmt} AS weapon_type"),
                'gr.character_id',
                'gr.game_rank',
                DB::raw('COUNT(*) as game_rank_count'),
                DB::raw('AVG(gr.mmr_gain) as avg_mmr_gain'),
                DB::raw('AVG(gr.team_kill_score) as avg_team_kill_score'),
                DB::raw('SUM(CASE WHEN (gr.mmr_gain + gr.mmr_cost) > 0 THEN 1 ELSE 0 END) as positive_count'),
                DB::raw('SUM(CASE WHEN (gr.mmr_gain + gr.mmr_cost) < 0 THEN 1 ELSE 0 END) as negative_count'),
                DB::raw('IFNULL(AVG(CASE WHEN gr.mmr_gain + gr.mmr_cost > 0 THEN gr.mmr_gain + gr.mmr_cost END), 0) as positive_avg_mmr_gain'),
                DB::raw('IFNULL(AVG(CASE WHEN gr.mmr_gain + gr.mmr_cost < 0 THEN gr.mmr_gain + gr.mmr_cost END), 0) as negative_avg_mmr_gain'),
            )
            ->where('gr.matching_mode', 3) // 랭크모드만
            ->groupBy('gr.character_id',
                DB::raw($weaponTypeCaseStmt),
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
        $weaponTypeCaseStmt = $this->getWeaponTypeCaseStatement();

        $result = DB::table($gameResultEquipmentOrderTableName . ' as gre')
            ->join($gameResultTableName . ' as gr', 'gr.id', '=', 'gre.game_result_id')
            ->join('equipments as e', 'gr.weapon_id', '=', 'e.id')
            ->select(
                'gre.equipment_id',
                'gr.character_id',
                'gr.game_rank',
                DB::raw("{$weaponTypeCaseStmt} AS weapon_type"),
                DB::raw('COUNT(*) as game_rank_count'),
                DB::raw('AVG(gr.mmr_gain) as avg_mmr_gain'),
                DB::raw('AVG(gr.team_kill_score) as avg_team_kill_score'),
                DB::raw('SUM(CASE WHEN (gr.mmr_gain + gr.mmr_cost) > 0 THEN 1 ELSE 0 END) as positive_count'),
                DB::raw('SUM(CASE WHEN (gr.mmr_gain + gr.mmr_cost) < 0 THEN 1 ELSE 0 END) as negative_count'),
                DB::raw('IFNULL(AVG(CASE WHEN gr.mmr_gain > 0 THEN gr.mmr_gain END), 0) as positive_avg_mmr_gain'),
                DB::raw('IFNULL(AVG(CASE WHEN gr.mmr_gain < 0 THEN gr.mmr_gain END), 0) as negative_avg_mmr_gain'),
            )
            ->where('gr.matching_mode', 3) // 랭크모드만
            ->groupBy(
                'gr.character_id',
                DB::raw($weaponTypeCaseStmt),
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
        $weaponTypeCaseStmt = $this->getWeaponTypeCaseStatement();

        $result = DB::table($gameResultTraitOrderTableName . ' as grt')
            ->join($gameResultTableName . ' as gr', 'gr.id', '=', 'grt.game_result_id')
            ->join('equipments as e', 'gr.weapon_id', '=', 'e.id')
            ->select(
                'grt.trait_id',
                'grt.is_main',
                'gr.character_id',
                'gr.game_rank',
                DB::raw("{$weaponTypeCaseStmt} AS weapon_type"),
                DB::raw('COUNT(*) as game_rank_count'),
                DB::raw('AVG(gr.mmr_gain) as avg_mmr_gain'),
                DB::raw('AVG(gr.team_kill_score) as avg_team_kill_score'),
                DB::raw('SUM(CASE WHEN (gr.mmr_gain + gr.mmr_cost) > 0 THEN 1 ELSE 0 END) as positive_count'),
                DB::raw('SUM(CASE WHEN (gr.mmr_gain + gr.mmr_cost) < 0 THEN 1 ELSE 0 END) as negative_count'),
                DB::raw('IFNULL(AVG(CASE WHEN gr.mmr_gain > 0 THEN gr.mmr_gain END), 0) as positive_avg_mmr_gain'),
                DB::raw('IFNULL(AVG(CASE WHEN gr.mmr_gain < 0 THEN gr.mmr_gain END), 0) as negative_avg_mmr_gain'),
            )
            ->where('gr.matching_mode', 3) // 랭크모드만
            ->groupBy(
                'gr.character_id',
                DB::raw($weaponTypeCaseStmt),
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
        $weaponTypeCaseStmt = $this->getWeaponTypeCaseStatement();

        $result = DB::table($gameResultTableName . ' as gr')
            ->join('equipments as e', 'gr.weapon_id', '=', 'e.id')
            ->select(
                'gr.tactical_skill_id',
                'gr.tactical_skill_level',
                'gr.character_id',
                'gr.game_rank',
                DB::raw("{$weaponTypeCaseStmt} AS weapon_type"),
                DB::raw('COUNT(*) as game_rank_count'),
                DB::raw('AVG(gr.mmr_gain) as avg_mmr_gain'),
                DB::raw('AVG(gr.team_kill_score) as avg_team_kill_score'),
                DB::raw('SUM(CASE WHEN (gr.mmr_gain + gr.mmr_cost) > 0 THEN 1 ELSE 0 END) as positive_count'),
                DB::raw('SUM(CASE WHEN (gr.mmr_gain + gr.mmr_cost) < 0 THEN 1 ELSE 0 END) as negative_count'),
                DB::raw('IFNULL(AVG(CASE WHEN gr.mmr_gain > 0 THEN gr.mmr_gain END), 0) as positive_avg_mmr_gain'),
                DB::raw('IFNULL(AVG(CASE WHEN gr.mmr_gain < 0 THEN gr.mmr_gain END), 0) as negative_avg_mmr_gain'),
            )
            ->where('gr.matching_mode', 3) // 랭크모드만
            ->groupBy(
                'gr.character_id',
                DB::raw($weaponTypeCaseStmt),
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
        $weaponTypeCaseStmt = $this->getWeaponTypeCaseStatement();

        $results = DB::table($gameResultTableName . ' as gr')
            ->leftJoin('equipments as e', 'gr.weapon_id', '=', 'e.id')
            ->leftJoin('characters as c', 'gr.character_id', '=', 'c.id')
            ->select(

                DB::raw('MAX(c.name) as name'), // ✅ `GROUP BY` 없이 가져오기
                DB::raw("{$weaponTypeCaseStmt} AS weapon_type"),
                'gr.character_id',
                DB::raw('COUNT(*) as game_count'),
                DB::raw('SUM(CASE WHEN (gr.mmr_gain + gr.mmr_cost) > 0 THEN 1 ELSE 0 END) as positive_count'),
                DB::raw('SUM(CASE WHEN (gr.mmr_gain + gr.mmr_cost) < 0 THEN 1 ELSE 0 END) as negative_count'),
                DB::raw('AVG(gr.mmr_gain) as avg_mmr_gain'),
                DB::raw('AVG(CASE WHEN (gr.mmr_gain + gr.mmr_cost) > 0 THEN (gr.mmr_gain + gr.mmr_cost) END) as avg_positive_mmr_gain'),
                DB::raw('AVG(CASE WHEN (gr.mmr_gain + gr.mmr_cost) < 0 THEN (gr.mmr_gain + gr.mmr_cost) END) as avg_negative_mmr_gain'),
                DB::raw('AVG(gr.team_kill_score) as avg_team_kill_score'),
                DB::raw('SUM(CASE WHEN gr.game_rank <= 4 THEN 1 ELSE 0 END) AS top4_count'),
                DB::raw('SUM(CASE WHEN gr.game_rank <= 2 THEN 1 ELSE 0 END) AS top2_count'),
                DB::raw('SUM(CASE WHEN gr.game_rank = 1 THEN 1 ELSE 0 END) AS top1_count')
            )
            ->where('gr.matching_mode', 3) // 랭크모드만
            ->whereNotNull('e.item_type2')
            ->groupBy(
                'gr.character_id',
                DB::raw($weaponTypeCaseStmt)
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
                'avgTeamKillScore' => $item->avg_team_kill_score !== null ? round($item->avg_team_kill_score,2) : 0,
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
        $metaStandard['gameCountPercent'] = (1 / count($data)) * 100 * 1.3;
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

        // 🔥 성능 최적화: WHERE 조건을 먼저 적용하여 JOIN 전 데이터 필터링
        $results = DB::table($gameResultTableName . ' as gr')
            ->where('gr.matching_mode', 3) // 랭크모드만 - 첫 번째 조건으로 (인덱스 활용)
            ->when(isset($filters['version_major']), function($query) use ($filters) {
                return $query->where('gr.version_major', $filters['version_major']);
            })
            ->when(isset($filters['version_minor']), function($query) use ($filters) {
                return $query->where('gr.version_minor', $filters['version_minor']);
            })
            ->when(isset($filters['min_tier']), function($query) use ($filters) {
                return $query->where('gr.mmr_before', '>=', $filters['min_score']);
            })
            ->join($gameResultEquipmentOrderTableName . ' as gre', 'gr.id', '=', 'gre.game_result_id')
            ->join('equipments as e', function($join) {
                $join->on('gre.equipment_id', '=', 'e.id')
                     ->whereNotNull('e.item_type2')
                     ->whereNotIn('e.item_type1', ['Weapon'])
                     ->whereIn('e.item_grade', ['Legend', 'Mythic']);
            })
            ->select(
                'gre.equipment_id',
                'e.item_grade',
                DB::raw('MAX(e.name) as name'),
                DB::raw('COUNT(*) as game_count'),
                DB::raw('SUM(CASE WHEN (gr.mmr_gain + gr.mmr_cost) > 0 THEN 1 ELSE 0 END) as positive_count'),
                DB::raw('SUM(CASE WHEN (gr.mmr_gain + gr.mmr_cost) < 0 THEN 1 ELSE 0 END) as negative_count'),
                DB::raw('AVG(gr.mmr_gain) as avg_mmr_gain'),
                DB::raw('AVG(CASE WHEN (gr.mmr_gain + gr.mmr_cost) > 0 THEN (gr.mmr_gain + gr.mmr_cost) END) as avg_positive_mmr_gain'),
                DB::raw('AVG(CASE WHEN (gr.mmr_gain + gr.mmr_cost) < 0 THEN (gr.mmr_gain + gr.mmr_cost) END) as avg_negative_mmr_gain'),
                DB::raw('AVG(gr.team_kill_score) as avg_team_kill_score'),
                DB::raw('SUM(CASE WHEN gr.game_rank <= 4 THEN 1 ELSE 0 END) AS top4_count'),
                DB::raw('SUM(CASE WHEN gr.game_rank <= 2 THEN 1 ELSE 0 END) AS top2_count'),
                DB::raw('SUM(CASE WHEN gr.game_rank = 1 THEN 1 ELSE 0 END) AS top1_count')
            )
            ->groupBy('gre.equipment_id', 'e.item_grade')
            ->orderBy('game_count', 'desc');

        $gameResults = $results->get();
        $total = array();
        $totalAll = 0;

        // 메타점수 계산용 변수
        $metaStandard = [
            'avgMmrGain' => 0,
        ];

        $data = [];
        foreach ($gameResults as $item) {
            // 등급별로 분리하기 위해 키에 item_grade 포함
            $key = $item->equipment_id . '_' . $item->item_grade;
            $data[$key] = [
                'equipmentId' => $item->equipment_id,
                'itemGrade' => $item->item_grade,
                'name' => $item->name, // 이름에 등급 표시
                'gameCount' => $item->game_count,
                'positiveGameCount' => $item->positive_count,
                'negativeGameCount' => $item->negative_count,
                'avgMmrGain' => round($item->avg_mmr_gain,1),
                'avgTeamKillScore' => $item->avg_team_kill_score !== null ? round($item->avg_team_kill_score,2) : 0,
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
        $metaStandard['gameCountPercent'] = (4 / count($data)) * 100 * 1.3;
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

        // 🔥 성능 최적화: WHERE 조건을 먼저 적용하여 JOIN 전 데이터 필터링
        $results = DB::table($gameResultTableName . ' as gr')
            ->where('gr.matching_mode', 3) // 랭크모드만 - 첫 번째 조건으로 (인덱스 활용)
            ->when(isset($filters['version_major']), function($query) use ($filters) {
                return $query->where('gr.version_major', $filters['version_major']);
            })
            ->when(isset($filters['version_minor']), function($query) use ($filters) {
                return $query->where('gr.version_minor', $filters['version_minor']);
            })
            ->when(isset($filters['min_tier']), function($query) use ($filters) {
                return $query->where('gr.mmr_before', '>=', $filters['min_score']);
            })
            ->join($gameResultFirstEquipmentOrderTableName . ' as gre', 'gr.id', '=', 'gre.game_result_id')
            ->join('equipments as e', function($join) {
                $join->on('gre.equipment_id', '=', 'e.id')
                     ->whereNotNull('e.item_type2')
                     ->whereNotIn('e.item_type1', ['Weapon'])
                     ->whereIn('e.item_grade', ['Epic']);
            })
            ->select(
                'gre.equipment_id',
                'e.item_grade',
                DB::raw('MAX(e.name) as name'),
                DB::raw('COUNT(*) as game_count'),
                DB::raw('SUM(CASE WHEN (gr.mmr_gain + gr.mmr_cost) > 0 THEN 1 ELSE 0 END) as positive_count'),
                DB::raw('SUM(CASE WHEN (gr.mmr_gain + gr.mmr_cost) < 0 THEN 1 ELSE 0 END) as negative_count'),
                DB::raw('AVG(gr.mmr_gain) as avg_mmr_gain'),
                DB::raw('AVG(CASE WHEN (gr.mmr_gain + gr.mmr_cost) > 0 THEN (gr.mmr_gain + gr.mmr_cost) END) as avg_positive_mmr_gain'),
                DB::raw('AVG(CASE WHEN (gr.mmr_gain + gr.mmr_cost) < 0 THEN (gr.mmr_gain + gr.mmr_cost) END) as avg_negative_mmr_gain'),
                DB::raw('AVG(gr.team_kill_score) as avg_team_kill_score'),
                DB::raw('SUM(CASE WHEN gr.game_rank <= 4 THEN 1 ELSE 0 END) AS top4_count'),
                DB::raw('SUM(CASE WHEN gr.game_rank <= 2 THEN 1 ELSE 0 END) AS top2_count'),
                DB::raw('SUM(CASE WHEN gr.game_rank = 1 THEN 1 ELSE 0 END) AS top1_count')
            )
            ->groupBy('gre.equipment_id', 'e.item_grade')
            ->orderBy('game_count', 'desc');

        $gameResults = $results->get();
        $total = array();
        $totalAll = 0;

        // 메타점수 계산용 변수
        $metaStandard = [
            'avgMmrGain' => 0,
        ];

        $data = [];
        foreach ($gameResults as $item) {
            // 등급별로 분리하기 위해 키에 item_grade 포함
            $key = $item->equipment_id . '_' . $item->item_grade;
            $data[$key] = [
                'equipmentId' => $item->equipment_id,
                'itemGrade' => $item->item_grade,
                'name' => $item->name,
                'gameCount' => $item->game_count,
                'positiveGameCount' => $item->positive_count,
                'negativeGameCount' => $item->negative_count,
                'avgMmrGain' => round($item->avg_mmr_gain,1),
                'avgTeamKillScore' => $item->avg_team_kill_score !== null ? round($item->avg_team_kill_score,2) : 0,
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

        // 픽률 점수: 로그 스케일로 계산 (1% 기준)
        $pickRateScore = log($pickRate / 0.01) / log(10); // 0.1%=-2, 1%=0, 10%=2, 100%=4
        $pickRateScore = max(-5, min(5, $pickRateScore)); // -5~5 범위로 제한

        // 성능 점수 계산
        $performanceScore = (
                $endGameScore * 0.2 +
                $top2Score * 0.2 +
                $top4Score * 0.2 +
                $mmrScore * 2.1
            );

        // 극저픽 페널티: 1% 미만일 때만 성능 감쇠
        $lowPickPenalty = 1.0;
        if ($pickRate < 0.01) {
            $lowPickPenalty = 0.3 + 0.7 * ($pickRate / 0.01); // 0.1%=0.37, 0.5%=0.65, 1%=1.0
        }
        $performanceScore = $performanceScore * $lowPickPenalty;

        // 픽률-성능 곱셈 시너지 (둘 다 좋아야 보너스)
        $pickNormalized = max(0, min(1, $pickRate / 0.05)); // 5% = 1.0
        $perfNormalized = max(0, min(1, ($performanceScore + 2) / 4)); // -2~2를 0~1로
        $synergy = sqrt($pickNormalized * $perfNormalized) * 3.0; // 기하평균 사용

        // 최종 메타 점수
        $metaScore = $performanceScore * 0.6 + $pickRateScore * 4.2 + $synergy * 0.6;

        // 디버깅용 변수 재할당
        $P_raw = $performanceScore / $lowPickPenalty;
        $P = $performanceScore;
        $pickAbsoluteScore = $pickRateScore;
        $performanceNormalized = $perfNormalized;

        // 디버깅용 로그 (특정 케이스만)
        if (isset($data['characterName']) && in_array($data['characterName'], ['히스이', '케네스'])) {
            \Log::info("Meta Score Debug - {$data['characterName']}", [
                'pickRate' => $pickRate,
                'pickAbsoluteScore' => $pickAbsoluteScore,
                'pickNormalized' => $pickNormalized,
                'stabilityFactor' => $stabilityFactor,
                'P_raw' => $P_raw,
                'P' => $P,
                'performanceNormalized' => $performanceNormalized,
                'synergy' => $synergy,
                'metaScore' => $metaScore,
                'avgMmrGain' => $data['avgMmrGain'],
                'mmrDelta' => $mmrDelta,
                'mmrScore' => $mmrScore,
                'top1Score' => $top1Score,
            ]);
        }

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

        // 픽률 계산 (장비는 /5 적용)
        $pickRate = max($data['gameCountPercent'] / 5 / 100, 0.001); // 최소 0.1%
        $stabilityFactor = log(1 + $pickRate) / log(1 + 0.05);   // 5% 이상이면 1.0
        // $stabilityFactor = min($stabilityFactor, 1.0);

        // 픽률 점수: 로그 스케일로 계산 (1% 기준)
        $pickRateScore = log($pickRate / 0.01) / log(10); // 0.1%=-2, 1%=0, 10%=2, 100%=4
        $pickRateScore = max(-20, min(20, $pickRateScore)); // -5~5 범위로 제한

        // 성능 점수 계산
        $performanceScore = (
            $endGameScore * 0.2 +
            $top2Score * 0.2 +
            $top4Score * 0.2 +
            $mmrScore * 2.1
        );

        // 극저픽 페널티: 1% 미만일 때만 성능 감쇠
        $lowPickPenalty = 1.0;
        if ($pickRate < 0.01) {
            $lowPickPenalty = 0.3 + 0.7 * ($pickRate / 0.01); // 0.1%=0.37, 0.5%=0.65, 1%=1.0
        }
        $performanceScore = $performanceScore * $lowPickPenalty;

        // 픽률-성능 곱셈 시너지 (둘 다 좋아야 보너스)
        $pickNormalized = max(0, min(1, $pickRate / 0.05)); // 5% = 1.0
        $perfNormalized = max(0, min(1, ($performanceScore + 2) / 4)); // -2~2를 0~1로
        $synergy = sqrt($pickNormalized * $perfNormalized) * 3.0; // 기하평균 사용

        // 최종 메타 점수
        $metaScore = $performanceScore * 0.6 + $pickRateScore * 4.2 + $synergy * 0.6;

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

    /**
     * 특정 게임 ID 이후의 모든 게임 결과 데이터 삭제
     * @param int $fromGameId
     * @return void
     */
    private function deleteGameResultsFrom(int $fromGameId): void
    {
        try {
            // 모든 버전의 game_results 테이블 찾기
            $versionHistories = VersionHistory::all();

            foreach ($versionHistories as $version) {
                $filters = [
                    'version_season' => $version->version_season,
                    'version_major' => $version->version_major,
                    'version_minor' => $version->version_minor,
                ];

                $gameResultTableName = VersionedGameTableManager::getTableName('game_results', $filters);
                $gameResultSkillOrderTableName = VersionedGameTableManager::getTableName('game_result_skill_orders', $filters);
                $gameResultEquipmentOrderTableName = VersionedGameTableManager::getTableName('game_result_equipment_orders', $filters);
                $gameResultFirstEquipmentOrderTableName = VersionedGameTableManager::getTableName('game_result_first_equipment_orders', $filters);
                $gameResultTraitOrderTableName = VersionedGameTableManager::getTableName('game_result_trait_orders', $filters);

                // 테이블이 존재하는지 확인
                if (DB::getSchemaBuilder()->hasTable($gameResultTableName)) {
                    // 해당 게임 ID 이상의 데이터 찾기
                    $gameResultIds = DB::table($gameResultTableName)
                        ->where('game_id', '>=', $fromGameId)
                        ->pluck('id');

                    if ($gameResultIds->isNotEmpty()) {
                        // 관련 테이블 먼저 삭제
                        if (DB::getSchemaBuilder()->hasTable($gameResultSkillOrderTableName)) {
                            DB::table($gameResultSkillOrderTableName)
                                ->whereIn('game_result_id', $gameResultIds)
                                ->delete();
                        }

                        if (DB::getSchemaBuilder()->hasTable($gameResultEquipmentOrderTableName)) {
                            DB::table($gameResultEquipmentOrderTableName)
                                ->whereIn('game_result_id', $gameResultIds)
                                ->delete();
                        }

                        if (DB::getSchemaBuilder()->hasTable($gameResultFirstEquipmentOrderTableName)) {
                            DB::table($gameResultFirstEquipmentOrderTableName)
                                ->whereIn('game_result_id', $gameResultIds)
                                ->delete();
                        }

                        if (DB::getSchemaBuilder()->hasTable($gameResultTraitOrderTableName)) {
                            DB::table($gameResultTraitOrderTableName)
                                ->whereIn('game_result_id', $gameResultIds)
                                ->delete();
                        }

                        // 메인 테이블 삭제
                        $deletedCount = DB::table($gameResultTableName)
                            ->where('game_id', '>=', $fromGameId)
                            ->delete();

                        if ($deletedCount > 0) {
                            Log::channel('fetchGameResultData')->info("Deleted {$deletedCount} game results from {$gameResultTableName} (game_id >= {$fromGameId})");
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::channel('fetchGameResultData')->error('Error deleting game results: ' . $e->getMessage());
        }
    }
}
