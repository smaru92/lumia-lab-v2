<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 캐릭터 스킬 이름 테이블 보강
 *
 * 이름/설명은 공식 API 의 언어 데이터(l10n)에서 가져온다. (fetch:character-skills)
 * id 는 전적의 skill_id 와 같은 값이라 그대로 조인할 수 있다. (1014200 -> 14번 캐릭터 Q)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('character_skills', function (Blueprint $table) {
            // 설명은 255자를 넘는 경우가 많고(최대 1,700여 자) 없는 스킬도 있다
            $table->text('tooltip')->nullable()->change();
            $table->integer('character_id')->nullable()->after('id')->index();
            // skill_id 백의 자리 - 1:T 2:Q 3:W 4:E 5:R, 6~9 는 변형 스킬
            $table->unsignedTinyInteger('slot')->nullable()->after('character_id');
        });
    }

    public function down(): void
    {
        Schema::table('character_skills', function (Blueprint $table) {
            $table->dropIndex(['character_id']);
            $table->dropColumn(['character_id', 'slot']);
            $table->string('tooltip')->change();
        });
    }
};
