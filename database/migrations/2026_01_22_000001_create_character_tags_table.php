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
        // 태그 테이블
        Schema::create('character_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // 캐릭터-태그 피벗 테이블
        Schema::create('character_character_tag', function (Blueprint $table) {
            $table->id();
            $table->integer('character_id');
            $table->unsignedBigInteger('character_tag_id');
            $table->timestamps();

            $table->foreign('character_id')
                ->references('id')
                ->on('characters')
                ->onDelete('cascade');

            $table->foreign('character_tag_id')
                ->references('id')
                ->on('character_tags')
                ->onDelete('cascade');

            $table->unique(['character_id', 'character_tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('character_character_tag');
        Schema::dropIfExists('character_tags');
    }
};
