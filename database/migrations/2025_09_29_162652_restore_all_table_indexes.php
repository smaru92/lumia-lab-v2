<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 각 테이블별로 인덱스 추가
        $this->addIndexesToTable('cache', function (Blueprint $table) {
            // cache 테이블의 인덱스 (마이그레이션 파일에서 복사)
            if (!$this->indexExists('cache', 'cache_key_index')) {
                $table->index('key');
            }
        });

        $this->addIndexesToTable('equipments', function (Blueprint $table) {
            if (!$this->indexExists('equipments', 'equipments_name_index')) {
                $table->index('name');
            }
            // 다른 인덱스들...
        });

        $this->addIndexesToTable('users', function (Blueprint $table) {
            if (!$this->indexExists('users', 'users_email_unique')) {
                $table->unique('email');
            }
        });

        // 다른 모든 테이블들...
    }

    public function down()
    {
        // Rollback 로직
    }

    private function addIndexesToTable($tableName, $callback)
    {
        if (Schema::hasTable($tableName)) {
            Schema::table($tableName, $callback);
        }
    }

    private function indexExists($table, $indexName)
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }
};