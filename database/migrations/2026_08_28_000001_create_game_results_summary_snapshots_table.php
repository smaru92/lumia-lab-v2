<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 캐릭터 지표의 일자별 스냅샷.
     *
     * game_results_summary 는 집계 때마다 섀도 테이블로 통째 교체되므로 직전 값이 남지 않는다.
     * 추이를 보려면 따로 적재해야 한다.
     *
     * 버전별로 테이블을 쪼개지 않는다. 시간·버전을 가로질러 조회하는 것이 목적이기 때문이다.
     */
    public function up(): void
    {
        Schema::create('game_results_summary_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('captured_date')->comment('스냅샷 일자 (하루 1건)');
            $table->string('version_key', 20)->comment('버전 키 (예: 12.2.0b)');

            $table->integer('character_id');
            $table->string('character_name', 50)->nullable();
            $table->string('weapon_type', 30);
            $table->string('min_tier', 20);

            $table->string('meta_tier', 10)->nullable()->comment('티어 (OP/1~5/RIP)');
            $table->float('meta_score')->nullable();
            $table->integer('game_count')->default(0);
            $table->float('game_count_percent')->nullable()->comment('픽률');
            $table->float('top1_count_percent')->nullable()->comment('승률');
            $table->float('top2_count_percent')->nullable();
            $table->float('top4_count_percent')->nullable();
            $table->float('avg_mmr_gain')->nullable()->comment('평균획득점수');
            $table->float('avg_team_kill_score')->nullable();
            $table->float('endgame_win_percent')->nullable();

            $table->timestamp('created_at')->nullable();

            // 하루 1건 보장 (같은 날 두 버전이 걸칠 수 있어 version_key 포함)
            $table->unique(
                ['captured_date', 'version_key', 'character_id', 'weapon_type', 'min_tier'],
                'summary_snapshot_unique'
            );
            // 캐릭터 상세/관리자 조회 경로
            $table->index(
                ['character_id', 'weapon_type', 'min_tier', 'captured_date'],
                'summary_snapshot_lookup'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_results_summary_snapshots');
    }
};
