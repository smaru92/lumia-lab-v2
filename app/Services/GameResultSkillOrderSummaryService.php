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
 * 스킬 순서는 skill_id 의 백의 자리 슬롯으로 표현한다. (1033200 -> 2 = Q)
 *   1:T(패시브) 2:Q 3:W 4:E 5:R, 6~9 는 일부 캐릭터의 변형 스킬(Q/W/E/R + 4)
 * 같은 슬롯의 변형(1055220 등)은 같은 키로 본다.
 *
 * 1) skill_orders -> skill_builds : 게임 1건을 순서 문자열 1행으로 접는다 (새 행만 증분 반영)
 * 2) skill_builds -> summary      : 9/11/14레벨까지의 순서별로 티어마다 집계한다
 */
class GameResultSkillOrderSummaryService
{
    use ErDevTrait;

    /**
     * 몇 레벨까지의 순서로 묶을지
     * 만렙(20)까지 가는 게임은 약 15%라 생존 편향이 커서, 대부분의 게임이 도달하는 구간까지만 본다.
     */
    public const LEVEL_CUTS = [9, 11, 14];

    /** (캐릭터, 무기, 레벨, 티어)마다 저장할 순서 개수 - 상위 10개로 전체 게임의 75~88%가 덮인다 */
    private const TOP_N = 30;

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
     * 게임별 순서를 레벨 구간 x 티어 단위로 집계한다.
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
        $minCut = min(self::LEVEL_CUTS);
        $maxCut = max(self::LEVEL_CUTS);

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
                    LEFT(b.skill_order, {$maxCut}) AS skill_prefix,
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
                ->whereRaw('CHAR_LENGTH(b.skill_order) >= ?', [$minCut])
                ->groupBy('weapon_type', 'skill_prefix', 'band')
                ->get();

            // [무기][레벨][순서][구간] 누적
            $bySequence = [];
            $totals = [];
            foreach ($rows as $row) {
                $length = strlen($row->skill_prefix);
                foreach (self::LEVEL_CUTS as $cut) {
                    if ($length < $cut) {
                        continue;
                    }
                    $sequence = substr($row->skill_prefix, 0, $cut);
                    $this->addMetrics($bySequence[$row->weapon_type][$cut][$sequence][$row->band], $row);
                    $totals[$row->weapon_type][$cut][$row->band] = ($totals[$row->weapon_type][$cut][$row->band] ?? 0) + $row->game_count;
                }
            }
            unset($rows);

            $insertRows = [];
            $now = now();
            foreach ($tiers as $tier) {
                foreach ($bySequence as $weaponType => $cuts) {
                    foreach ($cuts as $cut => $sequences) {
                        $levelGameCount = 0;
                        foreach ($totals[$weaponType][$cut] as $band => $count) {
                            if ($band >= $tier['band_from']) {
                                $levelGameCount += $count;
                            }
                        }
                        if ($levelGameCount === 0) {
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
                            $insertRows[] = $this->buildSummaryRow($character, $weaponType, $cut, (string) $sequence, $tier, $levelGameCount, $m, $now);
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
            echo "skill order summary : {$character->name} " . count($insertRows) . " rows\n";
        }

        // 이번 버전에서 사라진 캐릭터 정리
        DB::table($summaryTable)->whereNotIn('character_id', $processedCharacterIds)->delete();

        Log::channel($this->logChannel)->info("Inserted {$totalInserted} records into {$summaryTable}");
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
     * 캐릭터 상세 - 레벨 구간별 스킬 순서 통계
     *
     * @return array ['levels' => [9 => ['total' => 게임수, 'data' => [...]], 11 => ..., 14 => ...]]
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
            return ['levels' => []];
        }

        $query = DB::table($tableName)
            ->where('character_name', $filters['character_name'])
            ->where('min_tier', $filters['min_tier']);

        $weaponType = $this->replaceWeaponType($filters['weapon_type'] ?? 'All', 'en');
        if ($weaponType !== 'All') {
            $query->where('weapon_type', $weaponType);
        }

        $rows = $query->whereIn('level_cut', self::LEVEL_CUTS)
            ->orderBy('level_cut')->orderByDesc('game_count')->get();

        $levels = [];
        foreach ($rows as $row) {
            $cut = (int) $row->level_cut;
            $levels[$cut] ??= ['total' => 0, 'data' => [], 'weapons' => []];
            // 무기 구분 없이 조회하면 무기별 모수를 한 번씩만 더한다
            if (!isset($levels[$cut]['weapons'][$row->weapon_type])) {
                $levels[$cut]['weapons'][$row->weapon_type] = true;
                $levels[$cut]['total'] += (int) $row->level_game_count;
            }
            $levels[$cut]['data'][] = $row;
        }
        foreach ($levels as &$level) {
            unset($level['weapons']);
        }
        unset($level);

        return ['levels' => $levels];
    }
}
