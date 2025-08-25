<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateGamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('game_results', function (Blueprint $table) {
            $table->id();
            $table->integer('game_id')->comment('게임 id');
            $table->integer('user_id')->comment('유저 id');
            $table->integer('mmr_before')->comment('게임 시작전 점수');
            $table->integer('mmr_after')->comment('게임 시작후 점수');
            $table->integer('mmr_gain')->comment('점수 변동치');
            $table->integer('mmr_cost')->comment('게임 입장료');
            $table->integer('character_id')->comment('캐릭터 id');
            $table->integer('weapon_id')->comment('사용무기 id')->nullable();
            $table->integer('player_kill_score')->comment('개인 킬수');
            $table->integer('team_kill_score')->comment('팀 총합 킬수');
            $table->integer('player_death_score')->comment('개인 데스수');
            $table->integer('player_assist_score')->comment('개인 어시스트 수');
            $table->dateTime('start_at')->comment('게임 시작시간');
            $table->string('version_major')->comment('버전(메이저)');
            $table->string('version_minor')->comment('버전(마이너)');
            $table->timestamps();
        });



        Schema::create('game_result_skill_orders', function (Blueprint $table) {
            $table->id();
            $table->integer('game_result_id')->comment('게임 id');
            $table->integer('skill_id')->comment('스킬 id');
            $table->integer('order_level')->comment('스킬 찍은 순서');
            $table->timestamps();
        });

        Schema::create('game_result_trait_orders', function (Blueprint $table) {
            $table->id();
            $table->integer('game_result_id')->comment('게임 id');
            $table->integer('trait_id')->comment('스킬 id');
            $table->boolean('is_main')->comment('메인특성여부');
            $table->timestamps();
        });


        Schema::create('game_result_equipment_orders', function (Blueprint $table) {
            $table->id();
            $table->integer('game_result_id')->comment('게임 id');
            $table->integer('equipment_id')->comment('장비 id');
            $table->integer('equipment_grade')->comment('장비등급 1-일반, 2-고급, 3-희귀, 4-영웅, 5-전설, 6-신화');
            $table->integer('order_quipment')->comment('아이템 올린 순서, 현재는 미구현');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('games');
    }
}
