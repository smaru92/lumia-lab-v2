<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 핫픽스 알파벳(예: 12.1.1a)은 공식 API에서 제공하지 않으므로 수기로 관리한다.
     * 통계 집계 키(season.major.minor)에는 영향을 주지 않는 표기 전용 컬럼이다.
     */
    public function up(): void
    {
        Schema::table('version_histories', function (Blueprint $table) {
            $table->string('version_hotfix', 2)->nullable()->after('version_minor');
        });
    }

    public function down(): void
    {
        Schema::table('version_histories', function (Blueprint $table) {
            $table->dropColumn('version_hotfix');
        });
    }
};
