<?php

namespace App\Services;

use App\Models\VersionHistory;
use App\Traits\ErDevTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * 캐릭터별 스킬 순서 통계
 *
 * 스킬 순서는 skill_id 의 백의 자리 슬롯으로 저장한다. (1033200 -> 2)
 * 대부분 1:T(패시브) 2:Q 3:W 4:E 5:R 이지만 캐릭터마다 다르다. (띠아는 1·4·5·6·7 이 T·Q·W·E·R)
 * 그래서 집계할 때 slotKeyMap() 으로 캐릭터별 슬롯 -> 키(1:T 2:Q 3:W 4:E 5:R)를 바꿔 쓴다.
 * 같은 슬롯의 변형(1055220 등)은 같은 키로 본다.
 *
 * 1) skill_orders -> skill_builds : 게임 1건을 순서 문자열 1행으로 접는다 (새 행만 증분 반영)
 * 2) skill_builds -> summary      : 게임별 "마스터 순서"로 티어마다 집계한다
 *
 * 레벨 단위 순서는 1레벨 차이로 갈라져 의미가 없어서, 어떤 순서로 스킬을 마스터했는지로 묶는다.
 * 중간에 탈락한 게임도 살리려고 "2개 / 3개 / 4개를 마스터한 순서"를 따로 집계한다.
 */
class GameResultSkillOrderSummaryService
{
    use ErDevTrait;

    /**
     * 기본 마스터 레벨 (슬롯 => 기록상 포인트 수). R(5)은 6/11/16 고정이라 순서에서 뺀다.
     *
     * 캐릭터마다 다를 수 있어서 실제 집계에는 detectMasterLevels() 결과를 쓴다.
     * 시작할 때 자동으로 찍히는 스킬(Character.strUsePointLearnStartSkill, 예: 아드리아나 E)은
     * 그 1포인트가 전적의 스킬 순서와 최종 스킬 레벨 양쪽에서 빠져 있어 기록상 4가 최대다.
     * (자히르 W, 리오·칼라 Q, 아드리아나·제니·데비&마를렌·유민 E. 셀린은 Q 4 / T 1)
     */
    private const MASTER_LEVELS = [1 => 2, 2 => 5, 3 => 5, 4 => 5];

    /** 로그 표시용 키 이름 */
    private const SLOT_KEYS = [1 => 'T', 2 => 'Q', 3 => 'W', 4 => 'E'];

    /**
     * 슬롯 -> 키 기본 매핑 (character_skills 정보가 없을 때). 6~9 는 Q/W/E/R 의 변형으로 본다.
     * strtr() 로 순서 문자열을 한 번에 바꾸려고 문자 => 문자 형태로 둔다.
     */
    private const DEFAULT_SLOT_KEYS = [
        '1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5',
        '6' => '2', '7' => '3', '8' => '4', '9' => '5',
    ];

    private ?bool $hasSkillSlotColumns = null;

    /**
     * 만렙 게임의 기록상 스킬 포인트 수. 보통 20이고, 자동 습득 스킬이 있는 캐릭터는 19다.
     * 이 범위에서 긴 쪽부터 만렙 게임이 충분한 길이를 찾는다.
     */
    private const MAX_LEVEL_POINTS = [20, 19, 18];

    /** 캐릭터별 마스터 레벨을 정할 때 필요한 최소 만렙 게임 수 (미달이면 기본값) */
    private const MIN_MAX_LEVEL_GAMES = 10;

    /** 진짜 만렙 길이라면 슬롯별 포인트 조합이 사실상 하나로 모인다 */
    private const MIN_SIGNATURE_SHARE = 0.8;

    /**
     * 더 긴 길이의 게임이 이 비율보다 많으면 그 길이는 만렙이 아니다.
     * 일반 캐릭터는 만렙 직전(19포인트)에 모자란 스킬이 대체로 같아서 조합만으로는 구분이 안 되므로,
     * "20포인트 게임이 거의 없을 때만" 19를 만렙으로 본다. (아드리아나 등은 20포인트 게임이 아예 없다)
     */
    private const MAX_LONGER_RATIO = 0.05;

    /**
     * 몇 개를 마스터한 순서까지 볼지. 요약 테이블의 level_cut 에 이 값을 저장한다.
     * 순서 문자열은 마스터한 순서대로의 슬롯 N자리 (예: 23 = Q -> W, 2314 = Q -> W -> T -> E)
     * 4개 마스터는 Q/W/E/T 17포인트 + R 3포인트 = 만렙이라 표본이 크게 줄어든다. (랭크 표본 45명 중 4명)
     */
    public const MASTER_COUNTS = [2, 3, 4];

    /** 판정에 쓰는 앞부분 길이 (스킬 포인트는 20레벨까지 20개) */
    private const BUILD_PREFIX_LENGTH = 20;

    /** 가장 빨리 2개가 마스터되는 경우: Q 5 + T 2 = 7 */
    private const MIN_BUILD_LENGTH = 7;

    /** (캐릭터, 무기, 마스터 개수, 티어)마다 저장할 순서 개수 */
    private const TOP_N = 10;

    /** skill_orders 를 한 번에 읽을 id 구간 */
    private const SYNC_CHUNK = 500000;

    /** 구간 경계에 걸친 게임의 나머지 행을 찾을 범위 (게임 1건 = 최대 8명 x 20여 행) */
    private const TAIL_WINDOW = 5000;

    protected RankRangeService $rankRangeService;
    protected GameResultService $gameResultService;
    protected VersionedGameTableManager $versionedTableManager;
    protected string $logChannel = 'updateGameResultSkillOrderSummary';

    public function __construct()
    {
        $this->rankRangeService = new RankRangeService();
        $this->gameResultService = new GameResultService();
        $this->versionedTableManager = new VersionedGameTableManager();
    }

    /**
     * 스킬 순서 통계 갱신
     *
     * @param bool $rebuild 게임별 순서 테이블을 비우고 처음부터 다시 만든다
     */
    public function updateGameResultSkillOrderSummary($versionSeason = null, $versionMajor = null, $versionMinor = null, $versionHotfix = null, bool $rebuild = false)
    {
        DB::disableQueryLog();
        Log::channel($this->logChannel)->info('S: game result skill order summary');

        // 버전 인자가 하나도 없으면 최신 버전(핫픽스 포함)을 대상으로 집계한다.
        $versionGiven = $versionSeason !== null || $versionMajor !== null || $versionMinor !== null;
        $latestVersion = VersionHistory::active()->latest('created_at')->first();
        $versionSeason = $versionSeason ?? $latestVersion->version_season;
        $versionMajor = $versionMajor ?? $latestVersion->version_major;
        $versionMinor = $versionMinor ?? $latestVersion->version_minor;
        if (!$versionGiven) {
            $versionHotfix = $versionHotfix ?? $latestVersion->version_hotfix;
        }

        $versionFilters = [
            'version_season' => $versionSeason,
            'version_major' => $versionMajor,
            'version_minor' => $versionMinor,
            'version_hotfix' => $versionHotfix,
        ];

        $gameResultTable = VersionedGameTableManager::getTableName('game_results', $versionFilters);
        $skillOrderTable = VersionedGameTableManager::getTableName('game_result_skill_orders', $versionFilters);
        $buildTable = VersionedGameTableManager::getTableName('game_result_skill_builds', $versionFilters);
        $summaryTable = VersionedGameTableManager::getTableName('game_results_skill_order_summary', $versionFilters);

        if (!Schema::hasTable($gameResultTable) || !Schema::hasTable($skillOrderTable)) {
            Log::channel($this->logChannel)->info("Skip: {$gameResultTable} / {$skillOrderTable} not found");
            echo "skip : {$gameResultTable} 테이블이 없습니다.\n";
            return;
        }

        try {
            $this->syncSkillBuilds($skillOrderTable, $buildTable, $rebuild);
            $this->aggregate($gameResultTable, $buildTable, $summaryTable, $versionFilters);

            Log::channel($this->logChannel)->info('E: game result skill order summary');
        } catch (\Exception $e) {
            Log::channel($this->logChannel)->error('Error: ' . $e->getMessage());
            Log::channel($this->logChannel)->error($e->getTraceAsString());
            throw $e;
        } finally {
            gc_collect_cycles();
        }
    }

    /**
     * skill_orders 의 새 행을 게임별 순서 문자열로 접어 skill_builds 에 반영한다.
     *
     * skill_orders 에는 game_result_id 인덱스가 없으므로 PK 구간으로만 읽는다.
     * 수집기는 게임 1건의 스킬 행을 한 트랜잭션에서 한 번에 넣으므로
     * id 순서 = 게임 안의 레벨 순서이고, 커밋된 마지막 게임은 항상 완결 상태다.
     */
    public function syncSkillBuilds(string $skillOrderTable, string $buildTable, bool $rebuild = false): void
    {
        $this->versionedTableManager->ensureGameResultSkillBuildTableExists($buildTable);

        if ($rebuild) {
            DB::table($buildTable)->truncate();
            Log::channel($this->logChannel)->info("Truncated {$buildTable} (rebuild)");
        }

        $cursor = (int) DB::table($buildTable)->max('last_skill_order_id');
        $maxId = (int) DB::table($skillOrderTable)->max('id');

        Log::channel($this->logChannel)->info("Sync {$skillOrderTable} -> {$buildTable} : ({$cursor}, {$maxId}]");
        echo "skill build sync : {$cursor} -> {$maxId}\n";

        $synced = 0;
        while ($cursor < $maxId) {
            $end = min($cursor + self::SYNC_CHUNK, $maxId);

            // 구간 끝에 걸친 게임은 다음 구간으로 쪼개지지 않도록 나머지 행까지 끌어온다.
            if ($end < $maxId) {
                $lastGameResultId = DB::table($skillOrderTable)
                    ->where('id', '<=', $end)
                    ->orderByDesc('id')
                    ->value('game_result_id');

                $tailEnd = DB::table($skillOrderTable)
                    ->where('id', '>', $end)
                    ->where('id', '<=', $end + self::TAIL_WINDOW)
                    ->where('game_result_id', $lastGameResultId)
                    ->max('id');

                if ($tailEnd) {
                    $end = (int) $tailEnd;
                }
            }

            // 그래도 쪼개진 게임이 있으면 이어 붙인다 (id 순서 = 레벨 순서)
            $synced += DB::affectingStatement(
                "INSERT INTO `{$buildTable}` (game_result_id, skill_order, last_skill_order_id)
                 SELECT game_result_id,
                        GROUP_CONCAT((skill_id DIV 100) % 10 ORDER BY order_level SEPARATOR ''),
                        MAX(id)
                 FROM `{$skillOrderTable}`
                 WHERE id > ? AND id <= ?
                 GROUP BY game_result_id
                 ON DUPLICATE KEY UPDATE
                    skill_order = LEFT(CONCAT(skill_order, VALUES(skill_order)), 40),
                    last_skill_order_id = VALUES(last_skill_order_id)",
                [$cursor, $end]
            );

            echo "  synced ~{$end}\n";
            $cursor = $end;
        }

        Log::channel($this->logChannel)->info("Synced rows affected: {$synced}");
    }

    /**
     * 게임별 마스터 순서를 티어 단위로 집계한다.
     *
     * 티어는 "mmr_before >= 최소점수" 누적 구간이라, 티어마다 다시 조회하지 않고
     * 점수 구간(band)별로 한 번 집계한 뒤 PHP 에서 위쪽 구간을 누적한다.
     * 캐릭터 단위로 조회/교체하므로 집계 중에도 화면이 비지 않는다.
     */
    private function aggregate(string $gameResultTable, string $buildTable, string $summaryTable, array $versionFilters): void
    {
        $this->versionedTableManager->ensureGameResultSkillOrderSummaryTableExists($summaryTable);

        // 티어별 최소 점수와 점수 구간
        $tiers = [];
        foreach ($this->tierRange as $tier) {
            $tiers[] = [
                'min_tier' => $tier['tier'] . $tier['tierNumber'],
                'min_score' => (int) ($this->rankRangeService->getMinScore($tier['tier'], $tier['tierNumber'], $versionFilters) ?: 0),
            ];
        }
        $thresholds = array_values(array_unique(array_column($tiers, 'min_score')));
        sort($thresholds);

        $bandCase = 'CASE';
        for ($i = count($thresholds) - 1; $i >= 0; $i--) {
            $bandCase .= " WHEN gr.mmr_before >= {$thresholds[$i]} THEN {$i}";
        }
        $bandCase .= ' ELSE -1 END';

        foreach ($tiers as &$tier) {
            $tier['band_from'] = array_search($tier['min_score'], $thresholds, true);
        }
        unset($tier);

        $weaponTypeCase = $this->gameResultService->getWeaponTypeCaseStatement();
        $prefixLength = self::BUILD_PREFIX_LENGTH;

        // (matching_mode, character_id) 인덱스만 훑는다
        $characterIds = DB::table($gameResultTable)->where('matching_mode', 3)->distinct()->pluck('character_id');
        $characterNames = DB::table('characters')->whereIn('id', $characterIds)->pluck('name', 'id');

        $processedCharacterIds = [];
        $totalInserted = 0;

        foreach ($characterNames as $characterId => $characterName) {
            $character = (object) ['character_id' => $characterId, 'name' => $characterName];

            $rows = DB::table($gameResultTable . ' as gr')
                ->join($buildTable . ' as b', 'b.game_result_id', '=', 'gr.id')
                ->join('equipments as e', 'gr.weapon_id', '=', 'e.id')
                ->selectRaw("
                    {$weaponTypeCase} AS weapon_type,
                    LEFT(b.skill_order, {$prefixLength}) AS skill_prefix,
                    {$bandCase} AS band,
                    COUNT(*) AS game_count,
                    SUM(gr.mmr_gain) AS mmr_sum,
                    COUNT(gr.mmr_gain) AS mmr_n,
                    SUM(CASE WHEN gr.mmr_gain + gr.mmr_cost > 0 THEN 1 ELSE 0 END) AS pos_n,
                    SUM(CASE WHEN gr.mmr_gain + gr.mmr_cost > 0 THEN gr.mmr_gain + gr.mmr_cost ELSE 0 END) AS pos_sum,
                    SUM(CASE WHEN gr.mmr_gain + gr.mmr_cost < 0 THEN 1 ELSE 0 END) AS neg_n,
                    SUM(CASE WHEN gr.mmr_gain + gr.mmr_cost < 0 THEN gr.mmr_gain + gr.mmr_cost ELSE 0 END) AS neg_sum,
                    SUM(gr.team_kill_score) AS tk_sum,
                    COUNT(gr.team_kill_score) AS tk_n,
                    SUM(CASE WHEN gr.game_rank = 1 THEN 1 ELSE 0 END) AS top1,
                    SUM(CASE WHEN gr.game_rank <= 2 THEN 1 ELSE 0 END) AS top2,
                    SUM(CASE WHEN gr.game_rank <= 4 THEN 1 ELSE 0 END) AS top4
                ")
                ->where('gr.matching_mode', 3)
                ->where('gr.character_id', $character->character_id)
                ->whereNotNull('e.item_type2')
                ->whereRaw('CHAR_LENGTH(b.skill_order) >= ?', [self::MIN_BUILD_LENGTH])
                ->groupBy('weapon_type', 'skill_prefix', 'band')
                ->get();

            // [무기][마스터 개수][순서][구간] 누적. N개를 마스터하기 전에 끝난 게임은 그 탭의 모수에서도 뺀다.
            // 같은 문자열이 무기/구간별로 반복되므로 판정 결과를 재사용한다.
            $bySequence = [];
            $totals = [];
            $masteredMemo = [];
            $slotKeys = $this->slotKeyMap((int) $characterId);
            $masterLevels = $this->detectMasterLevels($rows, $slotKeys);
            foreach ($rows as $row) {
                $mastered = $masteredMemo[$row->skill_prefix] ??= $this->masteredSlots(strtr($row->skill_prefix, $slotKeys), $masterLevels);
                foreach (self::MASTER_COUNTS as $count) {
                    if (strlen($mastered) < $count) {
                        break;
                    }
                    $sequence = substr($mastered, 0, $count);
                    $this->addMetrics($bySequence[$row->weapon_type][$count][$sequence][$row->band], $row);
                    $totals[$row->weapon_type][$count][$row->band] = ($totals[$row->weapon_type][$count][$row->band] ?? 0) + $row->game_count;
                }
            }
            unset($rows);

            $insertRows = [];
            $now = now();
            foreach ($tiers as $tier) {
                foreach ($bySequence as $weaponType => $counts) {
                    foreach ($counts as $count => $sequences) {
                        $masteredGameCount = 0;
                        foreach ($totals[$weaponType][$count] as $band => $games) {
                            if ($band >= $tier['band_from']) {
                                $masteredGameCount += $games;
                            }
                        }
                        if ($masteredGameCount === 0) {
                            continue;
                        }

                        $merged = [];
                        foreach ($sequences as $sequence => $bands) {
                            $metrics = null;
                            foreach ($bands as $band => $bandMetrics) {
                                if ($band >= $tier['band_from']) {
                                    $this->addMetrics($metrics, (object) $bandMetrics);
                                }
                            }
                            if ($metrics !== null) {
                                $merged[$sequence] = $metrics;
                            }
                        }

                        uasort($merged, fn ($a, $b) => $b['game_count'] <=> $a['game_count']);

                        foreach (array_slice($merged, 0, self::TOP_N, true) as $sequence => $m) {
                            $insertRows[] = $this->buildSummaryRow($character, $weaponType, $count, (string) $sequence, $tier, $masteredGameCount, $m, $now);
                        }
                    }
                }
            }
            unset($bySequence, $totals);

            // 캐릭터 단위로 교체한다 (다른 캐릭터는 집계 중에도 조회 가능)
            DB::transaction(function () use ($summaryTable, $character, $insertRows) {
                DB::table($summaryTable)->where('character_id', $character->character_id)->delete();
                foreach (array_chunk($insertRows, 500) as $chunk) {
                    DB::table($summaryTable)->insert($chunk);
                }
            });

            $processedCharacterIds[] = $character->character_id;
            $totalInserted += count($insertRows);
            $levelsText = implode(' ', array_map(fn ($slot, $level) => self::SLOT_KEYS[$slot] . $level, array_keys($masterLevels), $masterLevels));
            echo "skill order summary : {$character->name} [{$levelsText}] " . count($insertRows) . " rows\n";
        }

        // 이번 버전에서 사라진 캐릭터 정리
        DB::table($summaryTable)->whereNotIn('character_id', $processedCharacterIds)->delete();

        Log::channel($this->logChannel)->info("Inserted {$totalInserted} records into {$summaryTable}");
    }

    /**
     * 캐릭터의 슬롯별 마스터 레벨(기록상 포인트 수)을 만렙 게임에서 찾는다.
     *
     * 만렙까지 찍은 게임은 모든 스킬이 최대치이므로, 그 게임의 슬롯별 포인트 수가 곧 마스터 레벨이다.
     * 만렙의 기록상 길이가 캐릭터마다 달라서(자동 습득 스킬이 있으면 19) 긴 길이부터 내려가며
     * 게임이 충분히 모인 길이를 쓴다. 이상치를 피하려고 그 안에서 게임수 기준 최빈값을 쓴다.
     * 만렙 게임이 적으면(신규 캐릭터 등) 기본값을 쓴다.
     *
     * @param iterable $rows skill_prefix(앞 20자리, 원래 슬롯), game_count 를 가진 집계 행
     * @param array<string,string> $slotKeys slotKeyMap() 결과
     * @return array<int,int> [키 => 마스터 레벨]
     */
    private function detectMasterLevels(iterable $rows, array $slotKeys): array
    {
        // [길이][키별 포인트 시그니처] => 게임수
        $signatures = [];
        foreach ($rows as $row) {
            // 만렙 판정은 순서에서 빠지는 별도 스킬까지 포함한 전체 포인트 수로 한다
            $length = strlen($row->skill_prefix);
            if (!in_array($length, self::MAX_LEVEL_POINTS, true)) {
                continue;
            }

            $points = $this->countPoints(strtr($row->skill_prefix, $slotKeys));
            $signature = implode(',', array_map(fn ($slot) => $points[$slot] ?? 0, array_keys(self::MASTER_LEVELS)));
            $signatures[$length][$signature] = ($signatures[$length][$signature] ?? 0) + (int) $row->game_count;
        }

        $longerGames = 0;
        foreach (self::MAX_LEVEL_POINTS as $length) {
            $games = array_sum($signatures[$length] ?? []);
            $hasLonger = $longerGames > $games * self::MAX_LONGER_RATIO;
            $longerGames += $games;

            if ($games < self::MIN_MAX_LEVEL_GAMES || $hasLonger) {
                continue;
            }

            arsort($signatures[$length]);
            if (reset($signatures[$length]) / $games < self::MIN_SIGNATURE_SHARE) {
                continue;
            }

            $levels = array_combine(
                array_keys(self::MASTER_LEVELS),
                array_map('intval', explode(',', (string) array_key_first($signatures[$length])))
            );

            // 만렙인데 한 번도 안 찍는 슬롯은 마스터 판정에서 뺀다 (0 이면 영원히 도달하지 않음)
            return array_filter($levels, fn ($level) => $level > 0);
        }

        return self::MASTER_LEVELS;
    }

    /**
     * 캐릭터별 슬롯 -> 키 매핑 (strtr 용, 키: 1:T 2:Q 3:W 4:E 5:R, '' = 순서에서 뺌)
     *
     * character_skills(공식 l10n 스킬 이름)에서 슬롯별 기본 스킬 이름을 보고 정한다.
     *   - 슬롯 번호 순으로 첫 번째가 T, 이후 이름이 다른 스킬 4개가 차례로 Q/W/E/R
     *     (띠아: 1·4·5·6·7 -> T·Q·W·E·R)
     *   - 앞 슬롯과 이름이 같으면 그 스킬의 변형으로 보고 같은 키 (제니 6·7·8 = Q·W·E)
     *   - 그 밖의 별도 스킬은 순서에서 뺀다 (블레어 시프트 러시, 실비아 바이크 스킬, 알렉스 변신 스킬)
     * 정보가 없거나(신규 캐릭터, 동기화 전) 스킬이 5개 미만이면 기본 매핑을 쓴다.
     *
     * @return array<string,string>
     */
    private function slotKeyMap(int $characterId): array
    {
        $this->hasSkillSlotColumns ??= Schema::hasTable('character_skills')
            && Schema::hasColumns('character_skills', ['character_id', 'slot']);

        if (!$this->hasSkillSlotColumns) {
            return self::DEFAULT_SLOT_KEYS;
        }

        // 슬롯마다 가장 작은 id(변형 번호 00)의 이름이 기본 스킬 이름이다
        $names = DB::table('character_skills')
            ->where('character_id', $characterId)
            ->whereNotNull('slot')
            ->orderBy('id')
            ->get(['slot', 'name'])
            ->unique('slot')
            ->pluck('name', 'slot')
            ->all();
        ksort($names);

        if (count($names) < 5) {
            return self::DEFAULT_SLOT_KEYS;
        }

        $map = array_fill_keys(array_map('strval', range(1, 9)), '');
        $keyByName = [];
        $nextKey = 1;
        foreach ($names as $slot => $name) {
            $name = trim((string) $name);
            if (isset($keyByName[$name])) {
                $map[(string) $slot] = $keyByName[$name];
            } elseif ($nextKey <= 5) {
                $map[(string) $slot] = $keyByName[$name] = (string) $nextKey++;
            }
        }

        return $map;
    }

    /**
     * 키별 포인트 수
     *
     * @param string $build 키로 바꾼 순서 문자열
     * @return array<int,int>
     */
    private function countPoints(string $build): array
    {
        $points = [];
        foreach (str_split($build) as $char) {
            $key = (int) $char;
            $points[$key] = ($points[$key] ?? 0) + 1;
        }

        return $points;
    }

    /**
     * 키 순서 문자열 -> 마스터한 순서대로의 키 (예: "231" = Q -> W -> T 까지 마스터)
     *
     * R 은 레벨이 고정이라 마스터 판정에 넣지 않는다 ($masterLevels 에 없음).
     *
     * @param string $build 키로 바꾼 순서 문자열 (slotKeyMap 적용 후)
     * @param array<int,int> $masterLevels [키 => 마스터 레벨] (detectMasterLevels 결과)
     * @return string 마스터한 스킬이 없으면 ''
     */
    private function masteredSlots(string $build, array $masterLevels): string
    {
        $levels = array_fill_keys(array_keys($masterLevels), 0);
        $mastered = '';

        foreach (str_split($build) as $char) {
            $key = (int) $char;
            if (!isset($masterLevels[$key])) {
                continue;
            }

            // 마스터 레벨에 "도달하는 순간" 한 번만 기록한다 (변형 합산으로 넘어가도 중복 기록하지 않는다)
            if (++$levels[$key] === $masterLevels[$key]) {
                $mastered .= $key;
            }
        }

        return $mastered;
    }

    /**
     * 합산 가능한 지표를 누적한다
     */
    private function addMetrics(?array &$target, object $row): void
    {
        $target ??= [
            'game_count' => 0, 'mmr_sum' => 0, 'mmr_n' => 0,
            'pos_n' => 0, 'pos_sum' => 0, 'neg_n' => 0, 'neg_sum' => 0,
            'tk_sum' => 0, 'tk_n' => 0, 'top1' => 0, 'top2' => 0, 'top4' => 0,
        ];

        foreach ($target as $key => $value) {
            $target[$key] = $value + (float) $row->$key;
        }
    }

    private function buildSummaryRow(object $character, string $weaponType, int $cut, string $sequence, array $tier, int $levelGameCount, array $m, $now): array
    {
        $gameCount = (int) $m['game_count'];
        $percent = fn ($count, $base) => $base ? round($count / $base * 100, 2) : 0;

        return [
            'character_id' => $character->character_id,
            'character_name' => $character->name,
            'weapon_type' => $weaponType,
            'level_cut' => $cut,
            'skill_order' => $sequence,
            'min_tier' => $tier['min_tier'],
            'min_score' => $tier['min_score'],
            'level_game_count' => $levelGameCount,
            'game_count' => $gameCount,
            'positive_game_count' => (int) $m['pos_n'],
            'negative_game_count' => (int) $m['neg_n'],
            'game_count_percent' => $percent($gameCount, $levelGameCount),
            'positive_game_count_percent' => $percent($m['pos_n'], $gameCount),
            'negative_game_count_percent' => $percent($m['neg_n'], $gameCount),
            'top1_count' => (int) $m['top1'],
            'top2_count' => (int) $m['top2'],
            'top4_count' => (int) $m['top4'],
            'top1_count_percent' => $percent($m['top1'], $gameCount),
            'top2_count_percent' => $percent($m['top2'], $gameCount),
            'top4_count_percent' => $percent($m['top4'], $gameCount),
            'endgame_win_percent' => $percent($m['top1'], $m['top2']),
            'avg_mmr_gain' => $m['mmr_n'] ? round($m['mmr_sum'] / $m['mmr_n'], 1) : 0,
            'positive_avg_mmr_gain' => $m['pos_n'] ? round($m['pos_sum'] / $m['pos_n'], 1) : 0,
            'negative_avg_mmr_gain' => $m['neg_n'] ? round($m['neg_sum'] / $m['neg_n'], 1) : 0,
            'avg_team_kill_score' => $m['tk_n'] ? round($m['tk_sum'] / $m['tk_n'], 2) : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * 캐릭터 상세 - 스킬 마스터 순서 통계
     *
     * @return array ['masters' => [2 => ['total' => 2개 이상 마스터한 게임수, 'data' => [...]], 3 => ..., 4 => ...]]
     */
    public function getDetail(array $filters): array
    {
        $versionFilters = [
            'version_season' => $filters['version_season'] ?? null,
            'version_major' => $filters['version_major'] ?? null,
            'version_minor' => $filters['version_minor'] ?? null,
            'version_hotfix' => $filters['version_hotfix'] ?? null,
        ];
        $tableName = VersionedGameTableManager::getTableName('game_results_skill_order_summary', $versionFilters);

        if (!Schema::hasTable($tableName)) {
            return ['masters' => []];
        }

        $query = DB::table($tableName)
            ->where('character_name', $filters['character_name'])
            ->where('min_tier', $filters['min_tier'])
            ->whereIn('level_cut', self::MASTER_COUNTS);

        $weaponType = $this->replaceWeaponType($filters['weapon_type'] ?? 'All', 'en');
        if ($weaponType !== 'All') {
            $query->where('weapon_type', $weaponType);
        }

        $masters = [];
        foreach ($query->get()->groupBy('level_cut') as $count => $rows) {
            $masters[(int) $count] = $this->mergeDetailRows($rows);
        }
        ksort($masters);

        return ['masters' => $masters];
    }

    /**
     * 무기 구분 없이 조회하면 같은 순서가 무기별로 따로 오므로 순서 단위로 합친다
     *
     * @return array ['total' => 모수, 'data' => 사용수 상위 TOP_N]
     */
    private function mergeDetailRows($rows): array
    {
        $total = 0;
        $countedWeapons = [];
        $merged = [];
        foreach ($rows as $row) {
            if (!isset($countedWeapons[$row->weapon_type])) {
                $countedWeapons[$row->weapon_type] = true;
                $total += (int) $row->level_game_count;
            }

            $m = &$merged[$row->skill_order];
            $m ??= [
                'game_count' => 0, 'positive_game_count' => 0, 'negative_game_count' => 0,
                'top1_count' => 0, 'top2_count' => 0, 'top4_count' => 0,
                'mmr_sum' => 0, 'pos_sum' => 0, 'neg_sum' => 0, 'tk_sum' => 0,
            ];
            $m['game_count'] += $row->game_count;
            $m['positive_game_count'] += $row->positive_game_count;
            $m['negative_game_count'] += $row->negative_game_count;
            $m['top1_count'] += $row->top1_count;
            $m['top2_count'] += $row->top2_count;
            $m['top4_count'] += $row->top4_count;
            $m['mmr_sum'] += $row->avg_mmr_gain * $row->game_count;
            $m['pos_sum'] += $row->positive_avg_mmr_gain * $row->positive_game_count;
            $m['neg_sum'] += $row->negative_avg_mmr_gain * $row->negative_game_count;
            $m['tk_sum'] += ($row->avg_team_kill_score ?? 0) * $row->game_count;
            unset($m);
        }

        $percent = fn ($count, $base) => $base ? round($count / $base * 100, 2) : 0;
        $data = [];
        foreach ($merged as $sequence => $m) {
            $n = $m['game_count'];
            $data[] = [
                'skill_order' => (string) $sequence,
                'game_count' => $n,
                'game_count_percent' => $percent($n, $total),
                'top1_count' => $m['top1_count'],
                'top2_count' => $m['top2_count'],
                'top4_count' => $m['top4_count'],
                'top1_count_percent' => $percent($m['top1_count'], $n),
                'top2_count_percent' => $percent($m['top2_count'], $n),
                'top4_count_percent' => $percent($m['top4_count'], $n),
                'endgame_win_percent' => $percent($m['top1_count'], $m['top2_count']),
                'avg_mmr_gain' => $n ? round($m['mmr_sum'] / $n, 1) : 0,
                'avg_team_kill_score' => $n ? round($m['tk_sum'] / $n, 2) : 0,
                'positive_game_count_percent' => $percent($m['positive_game_count'], $n),
                'negative_game_count_percent' => $percent($m['negative_game_count'], $n),
                'positive_avg_mmr_gain' => $m['positive_game_count'] ? round($m['pos_sum'] / $m['positive_game_count'], 1) : 0,
                'negative_avg_mmr_gain' => $m['negative_game_count'] ? round($m['neg_sum'] / $m['negative_game_count'], 1) : 0,
            ];
        }

        usort($data, fn ($a, $b) => $b['game_count'] <=> $a['game_count']);

        return ['total' => $total, 'data' => array_slice($data, 0, self::TOP_N)];
    }
}
