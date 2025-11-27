<?php
namespace App\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class VersionedGameTableManager
{
    public static function getTableName($tableName, $filters = array())
    {
        $versionSeason = $filters['version_season'] ?? '';
        $versionMajor = $filters['version_major'] ?? '';
        $versionMinor = $filters['version_minor'] ?? '';
        
        // 버전 정보 검증 - 숫자와 언더스코어만 허용
        if (!preg_match('/^[0-9_]+$/', $versionSeason) || 
            !preg_match('/^[0-9_]+$/', $versionMajor) || 
            !preg_match('/^[0-9_]+$/', $versionMinor)) {
            throw new \InvalidArgumentException('Invalid version format. Only numbers and underscores are allowed.');
        }
        
        // 테이블명 검증 - 영문자, 숫자, 언더스코어만 허용
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
            throw new \InvalidArgumentException('Invalid table name format.');
        }
        
        return $tableName . '_v' . $versionSeason . '_' . $versionMajor . '_' . $versionMinor;
    }
    public function ensureGameResultTableExists(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->increments('id');
                $table->integer('game_id')->comment('게임 id');
                $table->string('nickname', 100)->comment('플레이어 닉네임');
                $table->integer('matching_mode')->nullable();
                $table->integer('mmr_before')->nullable()->comment('게임 시작전 점수');
                $table->integer('mmr_after')->nullable()->comment('게임 시작후 점수');
                $table->integer('mmr_gain')->nullable()->comment('점수 변동치');
                $table->integer('mmr_cost')->comment('게임 입장료');
                $table->integer('union_rank')->nullable();
                $table->unsignedInteger('game_rank')->nullable();
                $table->integer('character_id')->comment('캐릭터 id');
                $table->integer('weapon_id')->nullable()->comment('사용무기 id');
                $table->string('tactical_skill_id')->nullable();
                $table->integer('tactical_skill_level')->nullable();
                $table->integer('player_kill_score')->comment('개인 킬수');
                $table->integer('team_kill_score')->comment('팀 총합 킬수');
                $table->integer('player_death_score')->comment('개인 데스수');
                $table->integer('player_assist_score')->comment('개인 어시스트 수');
                $table->timestamp('start_at')->comment('게임 시작시간');
                $table->integer('version_season')->comment('버전(시즌)');
                $table->integer('version_major')->comment('버전(메이저)');
                $table->integer('version_minor')->comment('버전(마이너)');
                $table->timestamp('created_at')->nullable();

                $table->unique(['game_id', 'nickname'], 'game_results_game_id_nickname_unique');

                // Summary 쿼리 최적화: matching_mode가 모든 쿼리의 첫 번째 WHERE 조건
                $table->index(['matching_mode', 'mmr_before', 'character_id'], 'idx_mode_mmr_char');
                $table->index(['matching_mode', 'character_id', 'weapon_id'], 'idx_mode_char_weapon');

                // 기존 인덱스
                $table->index(['character_id', 'weapon_id', 'game_rank', 'mmr_gain'], 'idx_game_results_character_weapon_rank');
                $table->index(['version_season', 'version_major', 'version_minor'], 'idx_game_results_version');
                $table->index(['character_id', 'game_rank'], 'idx_gr_char_rank_mmr');
                $table->index(['version_season', 'version_major', 'version_minor', 'character_id', 'game_rank', 'id'], 'idx_gr_ver_char_rank_id');
                $table->index(['version_season', 'version_major', 'version_minor', 'weapon_id', 'character_id', 'game_rank', 'id'], 'idx_gr_ver_weapon_char_rank_id');
                $table->index(['version_season', 'version_major', 'version_minor', 'mmr_before'], 'idx_gr_version_mmr');
                $table->index(['version_season', 'version_major', 'version_minor', 'mmr_before', 'mmr_gain', 'game_rank'], 'idx_gr_version_mmr_gain_rank');
            });
        }
    }

    public function ensureGameResultTraitOrderTableExists(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->integer('game_result_id')->comment('게임 id');
                $table->integer('trait_id')->comment('스킬 id');
                $table->string('category', 10)->nullable()->comment('특성분류');
                $table->boolean('is_main')->comment('메인특성여부');
                $table->timestamp('created_at')->nullable();

                $table->index(['game_result_id', 'trait_id', 'is_main'], 'idx_grt_game_result_id_trait_main');
            });
        }
    }

    public function ensureGameResultSkillOrderTableExists(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->integer('game_result_id')->comment('게임 결과 id');
                $table->integer('skill_id')->comment('스킬 id');
                $table->integer('order_level')->comment('스킬 찍은 순서');
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function ensureGameResultEquipmentOrderTableExists(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->integer('game_result_id')->comment('게임 id');
                $table->integer('equipment_id')->comment('장비 id');
                $table->integer('equipment_grade')->comment('장비등급 1-일반, 2-고급, 3-희귀, 4-영웅, 5-전설, 6-신화');
                $table->integer('order_quipment')->comment('아이템 올린 순서, 현재는 미구현');
                $table->timestamp('created_at')->nullable();

                $table->index(['equipment_id', 'game_result_id'], 'idx_gre_equip_result');
                $table->index(['game_result_id'], 'idx_gre_game_result_id');
                $table->index(['game_result_id', 'equipment_id'], 'idx_gre_result_equip');
            });
        }
    }

    public function ensureGameResultFirstEquipmentOrderTableExists(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->integer('game_result_id')->comment('게임 id');
                $table->integer('equipment_id')->comment('장비 id');
                $table->timestamp('created_at')->nullable();

                $table->index(['equipment_id', 'game_result_id'], 'idx_gre_equip_result');
                $table->index(['game_result_id'], 'idx_gre_game_result_id');
                $table->index(['game_result_id', 'equipment_id'], 'idx_gre_result_equip');
            });
        }
    }
}

