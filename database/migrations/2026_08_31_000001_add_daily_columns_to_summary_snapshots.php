<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 일일(그날 게임만) 지표를 누적 지표와 나란히 저장한다.
     *
     * 기존 컬럼은 "버전 시작 ~ 그날"의 누적값이라 사이트 표시값과 일치하지만,
     * 뒤로 갈수록 새 데이터가 희석돼 하루의 변화가 드러나지 않는다.
     * 두 기준을 모두 담아 화면에서 전환할 수 있게 한다.
     */
    public function up(): void
    {
        Schema::table('game_results_summary_snapshots', function (Blueprint $table) {
            $table->string('daily_meta_tier', 10)->nullable()->after('endgame_win_percent');
            $table->float('daily_meta_score')->nullable()->after('daily_meta_tier');
            $table->integer('daily_game_count')->nullable()->after('daily_meta_score');
            $table->float('daily_game_count_percent')->nullable()->after('daily_game_count');
            $table->float('daily_top1_count_percent')->nullable()->after('daily_game_count_percent');
            $table->float('daily_top2_count_percent')->nullable()->after('daily_top1_count_percent');
            $table->float('daily_top4_count_percent')->nullable()->after('daily_top2_count_percent');
            $table->float('daily_avg_mmr_gain')->nullable()->after('daily_top4_count_percent');
            $table->float('daily_avg_team_kill_score')->nullable()->after('daily_avg_mmr_gain');
            $table->float('daily_endgame_win_percent')->nullable()->after('daily_avg_team_kill_score');
        });
    }

    public function down(): void
    {
        Schema::table('game_results_summary_snapshots', function (Blueprint $table) {
            $table->dropColumn([
                'daily_meta_tier', 'daily_meta_score', 'daily_game_count', 'daily_game_count_percent',
                'daily_top1_count_percent', 'daily_top2_count_percent', 'daily_top4_count_percent',
                'daily_avg_mmr_gain', 'daily_avg_team_kill_score', 'daily_endgame_win_percent',
            ]);
        });
    }
};
