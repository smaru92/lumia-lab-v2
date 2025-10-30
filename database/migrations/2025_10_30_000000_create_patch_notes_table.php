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
        Schema::create('patch_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('version_history_id')
                ->constrained('version_histories')
                ->onDelete('cascade')
                ->comment('버전 히스토리 ID');
            $table->enum('category', ['캐릭터', '특성', '아이템', '시스템', '전술스킬', '기타'])
                ->comment('패치 구분');
            $table->integer('target_id')->nullable()
                ->comment('해당 구분의 테이블 ID (캐릭터/아이템 등의 ID)');
            $table->enum('patch_type', ['버프', '너프', '조정', '리워크', '신규', '삭제'])
                ->comment('패치 유형');
            $table->text('content')
                ->comment('패치 내용');
            $table->timestamps();

            // 인덱스 추가
            $table->index(['version_history_id', 'category']);
            $table->index(['category', 'target_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patch_notes');
    }
};
