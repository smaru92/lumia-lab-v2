<?php

namespace App\Console\Commands;

use App\Models\VersionHistory;
use App\Services\GameResultService;
use App\Services\RankRangeService;
use App\Traits\ErDevTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 지난 날짜의 일별 스냅샷을 소급 생성한다.
 *
 * 스냅샷은 오늘부터 쌓이므로 이미 지난 패치는 하루 단위 이력이 없다.
 * 다행히 전적 원본에 start_at 이 남아 있어, "그 날짜까지 치러진 게임만"으로
 * 다시 집계하면 그 시점의 지표를 재현할 수 있다.
 *
 * 주의: min_score(티어 커트라인)는 현재 rank_ranges 기준으로 계산되므로,
 * 당시 커트라인과 다르면 메타티어 경계가 미세하게 어긋날 수 있다.
 * 픽률/승률/평균획득점수는 영향을 받지 않는다.
 */
class BackfillDailySnapshots extends Command
{
    use ErDevTrait;

    protected $signature = 'snapshot:backfill-daily
        {version : 대상 버전 키 (예: 12.2.0b)}
        {--tier= : 특정 티어만 (생략하면 전체 티어)}
        {--date= : 특정 하루만 생성 (생략하면 버전 전체 기간)}
        {--dry-run : 대상 날짜만 출력}';

    protected $description = '지난 날짜의 일별 스냅샷을 원본 전적에서 소급 생성';

    public function handle(GameResultService $gameResultService, RankRangeService $rankRangeService): int
    {
        $versionKey = $this->argument('version');
        $parts = parse_version_key($versionKey);

        if ($parts['key'] !== strtolower($versionKey)) {
            $this->error("버전 키 형식이 올바르지 않습니다: {$versionKey}");
            return self::FAILURE;
        }

        $version = VersionHistory::query()
            ->where('version_season', $parts['version_season'])
            ->where('version_major', $parts['version_major'])
            ->where('version_minor', $parts['version_minor'])
            ->when($parts['version_hotfix'], fn ($q) => $q->where('version_hotfix', $parts['version_hotfix']))
            ->when(!$parts['version_hotfix'], fn ($q) => $q->whereNull('version_hotfix'))
            ->first();

        if (!$version) {
            $this->error("버전 이력을 찾을 수 없습니다: {$versionKey}");
            return self::FAILURE;
        }

        $tableName = \App\Services\VersionedGameTableManager::getTableName('game_results', $parts);
        if (!Schema::hasTable($tableName)) {
            $this->error("전적 테이블이 없습니다: {$tableName}");
            return self::FAILURE;
        }

        // 실제 데이터가 있는 구간을 쓴다 (버전 종료일은 다음 패치 시작까지 늘어져 있을 수 있다)
        $range = DB::table($tableName)->selectRaw('MIN(start_at) as first, MAX(start_at) as last')->first();
        if (!$range->first) {
            $this->error('해당 버전에 전적이 없습니다.');
            return self::FAILURE;
        }

        $start = Carbon::parse($range->first)->startOfDay();
        $end = Carbon::parse($range->last)->startOfDay();
        $today = now()->startOfDay();
        if ($end->gt($today)) {
            $end = $today;
        }

        $dates = [];
        if ($this->option('date')) {
            // 스케줄러가 매일 당일치만 만들 때 쓰는 경로
            $one = Carbon::parse($this->option('date'))->startOfDay();
            if ($one->lt($start) || $one->gt($end)) {
                $this->warn('  대상 날짜가 이 버전의 전적 구간 밖입니다: ' . $one->toDateString());
                return self::SUCCESS;
            }
            $dates[] = $one;
        } else {
            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $dates[] = $d->copy();
            }
        }

        $this->info("[{$versionKey}] {$tableName}");
        $this->line('  전적 구간: ' . $range->first . ' ~ ' . $range->last);
        $this->line('  생성 대상: ' . count($dates) . '일 (' . $start->toDateString() . ' ~ ' . $end->toDateString() . ')');

        if ($this->option('dry-run')) {
            $this->warn('  dry-run 이라 실제로 만들지 않았습니다.');
            return self::SUCCESS;
        }

        $tiers = $this->option('tier')
            ? array_values(array_filter($this->tierRange, fn ($t) => $t['tier'] === $this->option('tier')))
            : $this->tierRange;

        $bar = $this->output->createProgressBar(count($dates) * count($tiers));
        $bar->start();

        $totalRows = 0;

        foreach ($dates as $date) {
            // 그날 자정까지 = 그날 하루가 끝난 시점
            $cutoff = $date->copy()->endOfDay()->format('Y-m-d H:i:s');

            foreach ($tiers as $tier) {
                $minTier = $tier['tier'] . $tier['tierNumber'];
                $minScore = $rankRangeService->getMinScore($tier['tier'], $tier['tierNumber'], $parts) ?: 0;

                $baseFilters = [
                    'version_season' => $parts['version_season'],
                    'version_major' => $parts['version_major'],
                    'version_minor' => $parts['version_minor'],
                    'version_hotfix' => $parts['version_hotfix'],
                    'min_tier' => $minTier,
                    'min_score' => $minScore,
                ];

                // 누적: 버전 시작 ~ 그날
                $result = $gameResultService->getGameResultMain(
                    $baseFilters + ['max_start_at' => $cutoff]
                );

                // 일일: 그날 하루치만
                $dailyResult = $gameResultService->getGameResultMain(
                    $baseFilters + [
                        'max_start_at' => $cutoff,
                        'min_start_at' => $date->copy()->startOfDay()->format('Y-m-d H:i:s'),
                    ]
                );
                $daily = $dailyResult['data'] ?? [];

                $rows = [];
                foreach ($result['data'] ?? [] as $key => $item) {
                    $d = $daily[$key] ?? null;
                    $rows[] = [
                        'captured_date' => $date->toDateString(),
                        'version_key' => $versionKey,
                        'character_id' => $item['characterId'],
                        'character_name' => $item['name'],
                        'weapon_type' => $item['weaponType'],
                        'min_tier' => $minTier,
                        'meta_tier' => $item['metaTier'],
                        'meta_score' => $item['metaScore'],
                        'game_count' => $item['gameCount'],
                        'game_count_percent' => $item['gameCountPercent'],
                        'top1_count_percent' => $item['top1CountPercent'],
                        'top2_count_percent' => $item['top2CountPercent'],
                        'top4_count_percent' => $item['top4CountPercent'],
                        'avg_mmr_gain' => $item['avgMmrGain'],
                        'avg_team_kill_score' => $item['avgTeamKillScore'] ?? null,
                        'endgame_win_percent' => $item['endgameWinPercent'],
                        'daily_meta_tier' => $d['metaTier'] ?? null,
                        'daily_meta_score' => $d['metaScore'] ?? null,
                        'daily_game_count' => $d['gameCount'] ?? 0,
                        'daily_game_count_percent' => $d['gameCountPercent'] ?? null,
                        'daily_top1_count_percent' => $d['top1CountPercent'] ?? null,
                        'daily_top2_count_percent' => $d['top2CountPercent'] ?? null,
                        'daily_top4_count_percent' => $d['top4CountPercent'] ?? null,
                        'daily_avg_mmr_gain' => $d['avgMmrGain'] ?? null,
                        'daily_avg_team_kill_score' => $d['avgTeamKillScore'] ?? null,
                        'daily_endgame_win_percent' => $d['endgameWinPercent'] ?? null,
                        'created_at' => now(),
                    ];
                }

                foreach (array_chunk($rows, 500) as $chunk) {
                    DB::table('game_results_summary_snapshots')->upsert(
                        $chunk,
                        ['captured_date', 'version_key', 'character_id', 'weapon_type', 'min_tier'],
                        [
                            'character_name', 'meta_tier', 'meta_score', 'game_count', 'game_count_percent',
                            'top1_count_percent', 'top2_count_percent', 'top4_count_percent',
                            'avg_mmr_gain', 'avg_team_kill_score', 'endgame_win_percent',
                            'daily_meta_tier', 'daily_meta_score', 'daily_game_count', 'daily_game_count_percent',
                            'daily_top1_count_percent', 'daily_top2_count_percent', 'daily_top4_count_percent',
                            'daily_avg_mmr_gain', 'daily_avg_team_kill_score', 'daily_endgame_win_percent',
                            'created_at',
                        ]
                    );
                }

                $totalRows += count($rows);
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('생성 완료: ' . number_format($totalRows) . '건');

        // 이 버전이 실제로 플레이된 기간 밖의 스냅샷은 지운다.
        // 버전 단위 소급분은 "마지막 집계 시각"에 찍혀서 다음 버전 기간에 걸쳐 있고,
        // 그 상태로 두면 같은 날짜에 두 버전이 겹쳐 추이 선이 뒤엉킨다.
        if ($this->option('date')) {
            return self::SUCCESS;
        }

        $stale = DB::table('game_results_summary_snapshots')
            ->where('version_key', $versionKey)
            ->where(function ($q) use ($start, $end) {
                $q->where('captured_date', '<', $start->toDateString())
                    ->orWhere('captured_date', '>', $end->toDateString());
            })
            ->delete();

        if ($stale) {
            $this->line('  기간 밖 스냅샷 ' . number_format($stale) . '건 정리');
        }

        return self::SUCCESS;
    }
}
