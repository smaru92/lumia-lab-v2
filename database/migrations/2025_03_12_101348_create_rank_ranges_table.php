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
        Schema::create('rank_ranges', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('랭크명 한글');
            $table->string('grade1')->comment('랭크 등급 - 골드, 플래티넘, 다이아...');
            $table->string('grade2')->comment('랭크 세부등급 - 골드 1~4');
            $table->integer('min_score');
            $table->integer('max_score');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rank_ranges', function (Blueprint $table) {
            //
        });
    }
};
