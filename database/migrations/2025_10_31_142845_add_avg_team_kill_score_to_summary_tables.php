<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // game_results_summary 테이블에 avg_team_kill_score 추가
        Schema::table('game_results_summary', function (Blueprint $table) {
            $table->decimal('avg_team_kill_score', 10, 3)->nullable()->after('avg_mmr_gain');
        });

        // game_results_equipment_summary 테이블에 avg_team_kill_score 추가
        Schema::table('game_results_equipment_summary', function (Blueprint $table) {
            $table->decimal('avg_team_kill_score', 10, 3)->nullable()->after('avg_mmr_gain');
        });

        // game_results_equipment_main_summary 테이블에 avg_team_kill_score 추가
        Schema::table('game_results_equipment_main_summary', function (Blueprint $table) {
            $table->decimal('avg_team_kill_score', 10, 3)->nullable()->after('avg_mmr_gain');
        });

        // game_results_first_equipment_main_summary 테이블에 avg_team_kill_score 추가
        Schema::table('game_results_first_equipment_main_summary', function (Blueprint $table) {
            $table->decimal('avg_team_kill_score', 10, 3)->nullable()->after('avg_mmr_gain');
        });

        // game_results_rank_summary 테이블에 avg_team_kill_score 추가
        if (Schema::hasTable('game_results_rank_summary')) {
            Schema::table('game_results_rank_summary', function (Blueprint $table) {
                if (!Schema::hasColumn('game_results_rank_summary', 'avg_team_kill_score')) {
                    $table->decimal('avg_team_kill_score', 10, 3)->nullable()->after('avg_mmr_gain');
                }
            });
        }

        // game_results_tactical_skill_summary 테이블에 avg_team_kill_score 추가 (있다면)
        if (Schema::hasTable('game_results_tactical_skill_summary')) {
            Schema::table('game_results_tactical_skill_summary', function (Blueprint $table) {
                if (!Schema::hasColumn('game_results_tactical_skill_summary', 'avg_team_kill_score')) {
                    $table->decimal('avg_team_kill_score', 10, 3)->nullable()->after('avg_mmr_gain');
                }
            });
        }

        // game_results_trait_summary 테이블에 avg_team_kill_score 추가 (있다면)
        if (Schema::hasTable('game_results_trait_summary')) {
            Schema::table('game_results_trait_summary', function (Blueprint $table) {
                if (!Schema::hasColumn('game_results_trait_summary', 'avg_team_kill_score')) {
                    $table->decimal('avg_team_kill_score', 10, 3)->nullable()->after('avg_mmr_gain');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_results_summary', function (Blueprint $table) {
            $table->dropColumn('avg_team_kill_score');
        });

        Schema::table('game_results_equipment_summary', function (Blueprint $table) {
            $table->dropColumn('avg_team_kill_score');
        });

        Schema::table('game_results_equipment_main_summary', function (Blueprint $table) {
            $table->dropColumn('avg_team_kill_score');
        });

        Schema::table('game_results_first_equipment_main_summary', function (Blueprint $table) {
            $table->dropColumn('avg_team_kill_score');
        });

        if (Schema::hasTable('game_results_rank_summary')) {
            Schema::table('game_results_rank_summary', function (Blueprint $table) {
                if (Schema::hasColumn('game_results_rank_summary', 'avg_team_kill_score')) {
                    $table->dropColumn('avg_team_kill_score');
                }
            });
        }

        if (Schema::hasTable('game_results_tactical_skill_summary')) {
            Schema::table('game_results_tactical_skill_summary', function (Blueprint $table) {
                if (Schema::hasColumn('game_results_tactical_skill_summary', 'avg_team_kill_score')) {
                    $table->dropColumn('avg_team_kill_score');
                }
            });
        }

        if (Schema::hasTable('game_results_trait_summary')) {
            Schema::table('game_results_trait_summary', function (Blueprint $table) {
                if (Schema::hasColumn('game_results_trait_summary', 'avg_team_kill_score')) {
                    $table->dropColumn('avg_team_kill_score');
                }
            });
        }
    }
};
