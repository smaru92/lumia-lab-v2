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
        Schema::create('equipment_equipment_skill', function (Blueprint $table) {
            $table->id();
            $table->integer('equipment_id')->comment('장비 ID');
            $table->unsignedBigInteger('equipment_skill_id')->comment('장비 스킬 ID');
            $table->timestamps();

            // 외래키 설정
            $table->foreign('equipment_id')->references('id')->on('equipments')->onDelete('cascade');
            $table->foreign('equipment_skill_id')->references('id')->on('equipment_skills')->onDelete('cascade');

            // 중복 방지를 위한 유니크 인덱스
            $table->unique(['equipment_id', 'equipment_skill_id'], 'equipment_skill_unique');

            // 인덱스
            $table->index('equipment_id');
            $table->index('equipment_skill_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_equipment_skill');
    }
};
