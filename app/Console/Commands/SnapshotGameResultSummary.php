<?php

namespace App\Console\Commands;

use App\Models\VersionHistory;
use App\Services\VersionedGameTableManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 캐릭터 지표를 하루 1건씩 스냅샷으로 남긴다.
 *
 * game_results_summary 는 집계 때마다 통째로 교체되어 이력이 남지 않으므로,
 * 추이 조회용으로 별도 적재한다. 같은 날 다시 실행하면 그날 값을 덮어쓴다.
 */
class SnapshotGameResultSummary extends Command
{
    protected $signature = 'snapshot:game-results-summary
        {version? : 대상 버전 키 (예: 12.2.0b). 생략하면 현재 기본 버전}
        {--backfill : 남아 있는 모든 버전별 요약 테이블을 과거 이력으로 적재}';

    protected $description = '캐릭터 지표를 일자별 스냅샷으로 적재';

    /** 스냅샷에 담을 컬럼 (원본 27컬럼을 다 복사하지 않는다) */
    private const COLUMNS = [
        'character_id',
        'character_name',
        'weapon_type',
        'min_tier',
        'meta_tier',
        'meta_score',
        'game_count',
        'game_count_percent',
        'top1_count_percent',
        'top2_count_percent',
        'top4_count_percent',
        'avg_mmr_gain',
        'avg_team_kill_score',
        'endgame_win_percent',
    ];

    public function handle(): int
    {
        if ($this->option('backfill')) {
            return $this->backfill();
        }

        $versionKey = $this->argument('version') ?: default_version();
        $captured = now()->toDateString();

        $count = $this->snapshot($versionKey, $captured);
        $this->info("[{$captured}] {$versionKey} : " . number_format($count) . '건 적재');

        return self::SUCCESS;
    }

    /**
     * 남아 있는 버전별 요약 테이블을 과거 이력으로 적재한다.
     *
     * 각 테이블의 updated_at 이 그 패치의 마지막 집계 시점이라 패치 종료 무렵의 값에 해당한다.
     * 일자별 해상도는 없지만 패치별 추이를 바로 볼 수 있다.
     */
    private function backfill(): int
    {
        $tables = DB::select("SHOW TABLES LIKE 'game_results_summary_v%'");
        $total = 0;

        foreach ($tables as $table) {
            $tableName = array_values((array) $table)[0];

            // game_results_summary_v12_2_0b -> 12.2.0b
            if (!preg_match('/_v(\d+)_(\d+)_(\d+)([a-z]{1,2})?$/', $tableName, $m)) {
                continue;
            }
            $versionKey = "{$m[1]}.{$m[2]}.{$m[3]}" . ($m[4] ?? '');

            $captured = DB::table($tableName)->max('updated_at');
            if (!$captured) {
                $this->warn("  {$tableName}: updated_at 이 없어 건너뜀");
                continue;
            }

            $capturedDate = substr($captured, 0, 10);
            $count = $this->snapshot($versionKey, $capturedDate, $tableName);
            $total += $count;
            $this->line("  [{$capturedDate}] {$versionKey} : " . number_format($count) . '건');
        }

        $this->info('초기 적재 완료: ' . number_format($total) . '건');

        return self::SUCCESS;
    }

    /**
     * 한 버전의 요약을 지정 일자 스냅샷으로 적재 (같은 날은 덮어씀)
     */
    private function snapshot(string $versionKey, string $capturedDate, ?string $tableName = null): int
    {
        $parts = parse_version_key($versionKey);
        $tableName = $tableName ?: VersionedGameTableManager::getTableName('game_results_summary', $parts);

        if (!Schema::hasTable($tableName)) {
            $this->warn("테이블 없음: {$tableName}");
            return 0;
        }

        $now = now();
        $inserted = 0;

        DB::table($tableName)->orderBy('id')->chunk(500, function ($rows) use (
            $versionKey, $capturedDate, $now, &$inserted
        ) {
            $payload = $rows->map(function ($row) use ($versionKey, $capturedDate, $now) {
                $data = [
                    'captured_date' => $capturedDate,
                    'version_key' => $versionKey,
                    'created_at' => $now,
                ];

                foreach (self::COLUMNS as $column) {
                    $data[$column] = $row->$column ?? null;
                }

                return $data;
            })->all();

            // 같은 날 재실행 시 그날 값을 갱신한다
            DB::table('game_results_summary_snapshots')->upsert(
                $payload,
                ['captured_date', 'version_key', 'character_id', 'weapon_type', 'min_tier'],
                array_merge(self::COLUMNS, ['created_at'])
            );

            $inserted += count($payload);
        });

        return $inserted;
    }
}
