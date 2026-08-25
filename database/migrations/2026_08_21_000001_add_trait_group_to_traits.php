<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 특성 그룹(메인/서브1/서브2)은 공식 API에서 제공하지 않아 수기로 관리한다.
     * traits.trait_group 은 "현재" 그룹이며, 패치별 변경 이력은 trait_version_groups 에 쌓인다.
     */
    public function up(): void
    {
        Schema::table('traits', function (Blueprint $table) {
            $table->string('trait_group', 10)->nullable()->after('category')->comment('특성 그룹(main/sub1/sub2)');
        });

        // 기존 is_main 값으로 초기 그룹을 채워둔다. (서브는 일단 sub1, 이후 관리자에서 조정)
        if (Schema::hasColumn('traits', 'is_main')) {
            DB::table('traits')->update([
                'trait_group' => DB::raw("CASE WHEN is_main = 1 THEN 'main' ELSE 'sub1' END"),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('traits', function (Blueprint $table) {
            $table->dropColumn('trait_group');
        });
    }
};
