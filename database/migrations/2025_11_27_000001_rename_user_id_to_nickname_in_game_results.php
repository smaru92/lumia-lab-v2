<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * user_id 컬럼을 nickname으로 변경 (개인정보 보호 정책 변경)
     */
    public function up(): void
    {
        // 기본 game_results 테이블이 존재하면 수정
        if (Schema::hasTable('game_results')) {
            Schema::table('game_results', function (Blueprint $table) {
                // unique 제약 조건 삭제 (존재할 경우)
                try {
                    $table->dropUnique('game_results_game_id_user_id_unique');
                } catch (\Exception $e) {
                    // 인덱스가 없으면 무시
                }
            });

            Schema::table('game_results', function (Blueprint $table) {
                // user_id 컬럼을 nickname으로 변경
                $table->renameColumn('user_id', 'nickname');
            });

            Schema::table('game_results', function (Blueprint $table) {
                // 컬럼 타입 변경 (integer -> string)
                $table->string('nickname', 100)->comment('플레이어 닉네임')->change();
            });

            Schema::table('game_results', function (Blueprint $table) {
                // 새로운 unique 제약 조건 추가
                $table->unique(['game_id', 'nickname'], 'game_results_game_id_nickname_unique');
            });
        }

        // 버전별 game_results 테이블들도 수정
        $versionedTables = DB::select("SHOW TABLES LIKE 'game_results_v%'");

        foreach ($versionedTables as $tableObj) {
            $tableName = array_values((array)$tableObj)[0];

            // user_id 컬럼이 존재하는지 확인
            if (Schema::hasColumn($tableName, 'user_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    // unique 제약 조건 삭제 시도
                    try {
                        $table->dropUnique('game_results_game_id_user_id_unique');
                    } catch (\Exception $e) {
                        // 인덱스가 없으면 무시
                    }
                });

                Schema::table($tableName, function (Blueprint $table) {
                    $table->renameColumn('user_id', 'nickname');
                });

                Schema::table($tableName, function (Blueprint $table) {
                    $table->string('nickname', 100)->comment('플레이어 닉네임')->change();
                });

                Schema::table($tableName, function (Blueprint $table) {
                    $table->unique(['game_id', 'nickname'], 'game_results_game_id_nickname_unique');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 기본 game_results 테이블
        if (Schema::hasTable('game_results') && Schema::hasColumn('game_results', 'nickname')) {
            Schema::table('game_results', function (Blueprint $table) {
                try {
                    $table->dropUnique('game_results_game_id_nickname_unique');
                } catch (\Exception $e) {
                    // 인덱스가 없으면 무시
                }
            });

            Schema::table('game_results', function (Blueprint $table) {
                $table->renameColumn('nickname', 'user_id');
            });

            Schema::table('game_results', function (Blueprint $table) {
                $table->integer('user_id')->comment('유저 id')->change();
            });

            Schema::table('game_results', function (Blueprint $table) {
                $table->unique(['game_id', 'user_id'], 'game_results_game_id_user_id_unique');
            });
        }

        // 버전별 game_results 테이블들도 롤백
        $versionedTables = DB::select("SHOW TABLES LIKE 'game_results_v%'");

        foreach ($versionedTables as $tableObj) {
            $tableName = array_values((array)$tableObj)[0];

            if (Schema::hasColumn($tableName, 'nickname')) {
                Schema::table($tableName, function (Blueprint $table) {
                    try {
                        $table->dropUnique('game_results_game_id_nickname_unique');
                    } catch (\Exception $e) {
                        // 인덱스가 없으면 무시
                    }
                });

                Schema::table($tableName, function (Blueprint $table) {
                    $table->renameColumn('nickname', 'user_id');
                });

                Schema::table($tableName, function (Blueprint $table) {
                    $table->integer('user_id')->comment('유저 id')->change();
                });

                Schema::table($tableName, function (Blueprint $table) {
                    $table->unique(['game_id', 'user_id'], 'game_results_game_id_user_id_unique');
                });
            }
        }
    }
};
