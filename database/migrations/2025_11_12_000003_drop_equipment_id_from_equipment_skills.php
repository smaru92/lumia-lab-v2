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
        Schema::table('equipment_skills', function (Blueprint $table) {
            // 외래키 제약조건이 있으면 삭제
            if (Schema::hasColumn('equipment_skills', 'equipment_id')) {
                // 외래키 제약조건 삭제 시도
                try {
                    $table->dropForeign(['equipment_id']);
                } catch (\Exception $e) {
                    // 외래키가 없으면 무시
                }

                // equipment_id 컬럼 삭제
                $table->dropColumn('equipment_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment_skills', function (Blueprint $table) {
            $table->integer('equipment_id')->comment('장비 ID');
            $table->foreign('equipment_id')->references('id')->on('equipments')->onDelete('cascade');
            $table->index('equipment_id');
        });
    }
};
