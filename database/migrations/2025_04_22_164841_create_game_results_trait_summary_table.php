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
        Schema::create('game_results_trait_summary', function (Blueprint $table) {
            $table->id();
            $table->integer('trait_id')->nullable()->comment('특성 id');
            $table->boolean('is_main')->nullable()->comment('메인특성여부');
            $table->integer('character_id')->nullable()->comment('캐릭터 id');
            $table->string('weapon_type')->nullable()->comment('무기타입');
            $table->integer('game_rank')->nullable()->comment('순위');
            $table->integer('game_rank_count')->nullable()->comment('게임 수');
            $table->integer('positive_count')->nullable()->comment('이득 게임 수');
            $table->integer('negative_count')->nullable()->comment('손실 게임 수');
            $table->decimal('avg_mmr_gain', 10, 3)->nullable()->comment('평균 점수 획득');
            $table->decimal('positive_avg_mmr_gain', 10, 3)->nullable()->comment('평균 이득 점수 획득');
            $table->decimal('negative_avg_mmr_gain', 10, 3)->nullable()->comment('평균 손실 점수 획득');
            $table->string('min_tier')->nullable();
            $table->integer('min_score')->nullable();
            $table->integer('version_major')->nullable();
            $table->integer('version_minor')->nullable();
            $table->unique(['character_id', 'weapon_type', 'trait_id', 'min_tier', 'version_major', 'version_minor', 'game_rank'], 'game_results_trait_summary_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_results_trait_summary');
    }
};
