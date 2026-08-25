<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 특성 그룹의 버전별 변경 이력.
     * 매 패치마다 전체를 다시 입력하지 않고 "바뀐 특성만" 기록하며,
     * 조회 시에는 대상 버전 이하에서 가장 최근 기록을 사용한다.
     */
    public function up(): void
    {
        Schema::create('trait_version_groups', function (Blueprint $table) {
            $table->id();
            $table->integer('version_season')->comment('버전(시즌)');
            $table->integer('version_major')->comment('버전(메이저)');
            $table->integer('version_minor')->comment('버전(마이너)');
            $table->integer('trait_id')->comment('특성 id');
            $table->string('trait_group', 10)->comment('특성 그룹(main/sub1/sub2)');
            $table->timestamps();

            $table->unique(
                ['version_season', 'version_major', 'version_minor', 'trait_id'],
                'trait_version_groups_version_trait_unique'
            );
            $table->index(['trait_id'], 'trait_version_groups_trait_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trait_version_groups');
    }
};
