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
        Schema::create('game_results_trait_main_summary', function (Blueprint $table) {
            $table->id();
            $table->integer('trait_id')->comment('특성 id');
            $table->string('trait_name')->comment('특성 이름');
            $table->boolean('is_main')->comment('메인 특성 여부');
            $table->string('meta_tier')->nullable()->comment('메타 티어');
            $table->decimal('meta_score', 10, 3)->nullable()->comment('메타 점수');
            $table->string('min_tier')->comment('최소 티어');
            $table->integer('min_score')->comment('최소 점수');
            $table->integer('game_count')->comment('게임 수');
            $table->integer('positive_game_count')->comment('이득 게임 수');
            $table->integer('negative_game_count')->comment('손실 게임 수');
            $table->decimal('game_count_percent', 10, 3)->comment('픽률');
            $table->decimal('positive_game_count_percent', 10, 3)->comment('이득 확률');
            $table->decimal('negative_game_count_percent', 10, 3)->comment('손실 확률');
            $table->integer('top1_count')->comment('1위 횟수');
            $table->integer('top2_count')->comment('TOP2 횟수');
            $table->integer('top4_count')->comment('TOP4 횟수');
            $table->decimal('top1_count_percent', 10, 3)->comment('승률');
            $table->decimal('top2_count_percent', 10, 3)->comment('TOP2 비율');
            $table->decimal('top4_count_percent', 10, 3)->comment('TOP4 비율');
            $table->decimal('endgame_win_percent', 10, 3)->nullable()->comment('막금구 승률');
            $table->decimal('avg_mmr_gain', 10, 3)->comment('평균 획득 점수');
            $table->decimal('avg_team_kill_score', 10, 3)->nullable()->comment('평균 TK');
            $table->decimal('positive_avg_mmr_gain', 10, 3)->comment('이득 시 평균 획득 점수');
            $table->decimal('negative_avg_mmr_gain', 10, 3)->comment('손실 시 평균 획득 점수');
            $table->smallInteger('version_season')->nullable();
            $table->smallInteger('version_major');
            $table->smallInteger('version_minor');
            $table->unique(['trait_id', 'is_main', 'min_tier', 'version_season', 'version_major', 'version_minor'], 'game_results_trait_main_summary_unique');
            $table->index(['min_tier', 'version_season', 'version_major', 'version_minor'], 'trait_main_summary_version_idx');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_results_trait_main_summary');
    }
};