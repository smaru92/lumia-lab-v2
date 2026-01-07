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

                // 최적화된 인덱스 (중복 제거)
                // idx_game_results_version 삭제 - idx_gr_ver_char_rank_id의 서브셋
                // idx_gr_char_rank_mmr 삭제 - idx_game_results_character_weapon_rank의 서브셋
                // idx_gr_version_mmr 삭제 - idx_gr_version_mmr_gain_rank의 서브셋
                $table->index(['character_id', 'weapon_id', 'game_rank', 'mmr_gain'], 'idx_game_results_character_weapon_rank');
                $table->index(['version_season', 'version_major', 'version_minor', 'character_id', 'game_rank', 'id'], 'idx_gr_ver_char_rank_id');
                $table->index(['version_season', 'version_major', 'version_minor', 'weapon_id', 'character_id', 'game_rank', 'id'], 'idx_gr_ver_weapon_char_rank_id');
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
                $table->index(['game_result_id', 'trait_id'], 'idx_grt_game_result_trait');
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

                // 최적화된 인덱스 (중복 제거)
                // idx_gre_game_result_id 삭제 - idx_gre_result_equip의 서브셋
                $table->index(['equipment_id', 'game_result_id'], 'idx_gre_equip_result');
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

                // 최적화된 인덱스 (중복 제거)
                // idx_gre_game_result_id 삭제 - idx_gre_result_equip의 서브셋
                $table->index(['equipment_id', 'game_result_id'], 'idx_gre_equip_result');
                $table->index(['game_result_id', 'equipment_id'], 'idx_gre_result_equip');
            });
        }
    }

    // ==================== Summary Tables ====================

    public function ensureGameResultSummaryTableExists(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->integer('character_id');
                $table->string('character_name');
                $table->string('weapon_type');
                $table->string('min_tier');
                $table->integer('min_score');
                $table->integer('game_count');
                $table->integer('positive_game_count');
                $table->integer('negative_game_count');
                $table->decimal('game_count_percent', 10, 3);
                $table->decimal('positive_game_count_percent', 10, 3);
                $table->decimal('negative_game_count_percent', 10, 3);
                $table->integer('top1_count');
                $table->integer('top2_count');
                $table->integer('top4_count');
                $table->decimal('top1_count_percent', 10, 3);
                $table->decimal('top2_count_percent', 10, 3);
                $table->decimal('top4_count_percent', 10, 3);
                $table->decimal('endgame_win_percent', 10, 3)->nullable();
                $table->decimal('avg_mmr_gain', 10, 3);
                $table->decimal('positive_avg_mmr_gain', 10, 3);
                $table->decimal('negative_avg_mmr_gain', 10, 3);
                $table->decimal('avg_team_kill_score', 10, 3)->nullable();
                $table->unique(['character_id', 'weapon_type', 'min_tier'], 'summary_unique');
                $table->timestamps();
            });
        }
    }

    public function ensureGameResultRankSummaryTableExists(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->integer('character_id')->nullable();
                $table->string('character_name')->nullable();
                $table->string('weapon_type')->nullable();
                $table->integer('game_rank')->nullable();
                $table->integer('game_rank_count')->nullable();
                $table->decimal('avg_mmr_gain', 10, 3)->nullable();
                $table->integer('positive_count')->nullable();
                $table->integer('negative_count')->nullable();
                $table->decimal('positive_avg_mmr_gain', 10, 3)->nullable();
                $table->decimal('negative_avg_mmr_gain', 10, 3)->nullable();
                $table->string('min_tier')->nullable();
                $table->integer('min_score')->nullable();
                $table->decimal('avg_team_kill_score', 10, 3)->nullable();
                $table->unique(['character_id', 'weapon_type', 'min_tier', 'game_rank'], 'rank_summary_unique');
                $table->timestamps();
            });
        }
    }

    public function ensureGameResultEquipmentSummaryTableExists(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->integer('equipment_id')->nullable()->comment('장비 id');
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
                $table->unique(['character_id', 'weapon_type', 'equipment_id', 'min_tier', 'game_rank'], 'equipment_summary_unique');
                $table->timestamps();
            });
        }
    }

    public function ensureGameResultEquipmentMainSummaryTableExists(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->integer('equipment_id');
                $table->string('equipment_name');
                $table->string('meta_tier')->nullable();
                $table->decimal('meta_score', 10, 3)->nullable();
                $table->string('min_tier');
                $table->integer('min_score');
                $table->integer('game_count');
                $table->integer('positive_game_count');
                $table->integer('negative_game_count');
                $table->decimal('game_count_percent', 10, 3);
                $table->decimal('positive_game_count_percent', 10, 3);
                $table->decimal('negative_game_count_percent', 10, 3);
                $table->integer('top1_count');
                $table->integer('top2_count');
                $table->integer('top4_count');
                $table->decimal('top1_count_percent', 10, 3);
                $table->decimal('top2_count_percent', 10, 3);
                $table->decimal('top4_count_percent', 10, 3);
                $table->decimal('endgame_win_percent', 10, 3)->nullable();
                $table->decimal('avg_mmr_gain', 10, 3);
                $table->decimal('positive_avg_mmr_gain', 10, 3);
                $table->decimal('negative_avg_mmr_gain', 10, 3);
                $table->unique(['equipment_id', 'min_tier'], 'equipment_main_summary_unique');
                $table->timestamps();
            });
        }
    }

    public function ensureGameResultFirstEquipmentMainSummaryTableExists(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->integer('equipment_id');
                $table->string('equipment_name');
                $table->string('meta_tier')->nullable();
                $table->decimal('meta_score', 10, 3)->nullable();
                $table->string('min_tier');
                $table->integer('min_score');
                $table->integer('game_count');
                $table->integer('positive_game_count');
                $table->integer('negative_game_count');
                $table->decimal('game_count_percent', 10, 3);
                $table->decimal('positive_game_count_percent', 10, 3);
                $table->decimal('negative_game_count_percent', 10, 3);
                $table->integer('top1_count');
                $table->integer('top2_count');
                $table->integer('top4_count');
                $table->decimal('top1_count_percent', 10, 3);
                $table->decimal('top2_count_percent', 10, 3);
                $table->decimal('top4_count_percent', 10, 3);
                $table->decimal('endgame_win_percent', 10, 3);
                $table->decimal('avg_mmr_gain', 10, 3);
                $table->decimal('positive_avg_mmr_gain', 10, 3);
                $table->decimal('negative_avg_mmr_gain', 10, 3);
                $table->unique(['equipment_id', 'min_tier'], 'first_equipment_main_summary_unique');
                $table->timestamps();
            });
        }
    }

    public function ensureGameResultTacticalSkillSummaryTableExists(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->integer('tactical_skill_id')->nullable()->comment('전술스킬 id');
                $table->integer('tactical_skill_level')->nullable()->comment('전술스킬 레벨');
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
                $table->unique(['character_id', 'weapon_type', 'tactical_skill_id', 'tactical_skill_level', 'min_tier', 'game_rank'], 'tactical_skill_summary_unique');
                $table->timestamps();
            });
        }
    }

    public function ensureGameResultTraitSummaryTableExists(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
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
                $table->unique(['character_id', 'weapon_type', 'trait_id', 'min_tier', 'game_rank'], 'trait_summary_unique');
                $table->timestamps();
            });
        }
    }

    public function ensureGameResultTraitCombinationSummaryTableExists(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->integer('character_id');
                $table->string('character_name');
                $table->string('weapon_type');
                $table->string('trait_ids')->comment('정렬된 특성 ID 조합 (예: 101,205,308)');
                $table->string('min_tier');
                $table->integer('min_score');
                $table->integer('game_count');
                $table->integer('positive_game_count');
                $table->integer('negative_game_count');
                $table->decimal('game_count_percent', 10, 3)->comment('해당 캐릭터 내 픽률');
                $table->decimal('positive_game_count_percent', 10, 3);
                $table->decimal('negative_game_count_percent', 10, 3);
                $table->integer('top1_count');
                $table->integer('top2_count');
                $table->integer('top4_count');
                $table->decimal('top1_count_percent', 10, 3);
                $table->decimal('top2_count_percent', 10, 3);
                $table->decimal('top4_count_percent', 10, 3);
                $table->decimal('endgame_win_percent', 10, 3)->nullable();
                $table->decimal('avg_mmr_gain', 10, 3);
                $table->decimal('positive_avg_mmr_gain', 10, 3);
                $table->decimal('negative_avg_mmr_gain', 10, 3);
                $table->decimal('avg_team_kill_score', 10, 3)->nullable();
                $table->unique(['character_id', 'weapon_type', 'trait_ids', 'min_tier'], 'trait_combination_summary_unique');
                $table->index(['character_id', 'weapon_type'], 'trait_combination_char_weapon_idx');
                $table->timestamps();
            });
        }
    }

    public function ensureGameResultTraitMainSummaryTableExists(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, function (Blueprint $table) {
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
                $table->unique(['trait_id', 'is_main', 'min_tier'], 'trait_main_summary_unique');
                $table->timestamps();
            });
        }
    }
}

