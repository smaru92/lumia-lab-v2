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
        Schema::table('patch_notes', function (Blueprint $table) {
            $table->string('weapon_type')->nullable()->after('target_id')->comment('무기 타입 (캐릭터 전용)');
            $table->string('skill_type')->nullable()->after('weapon_type')->comment('스킬 타입 (캐릭터 전용)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patch_notes', function (Blueprint $table) {
            $table->dropColumn(['weapon_type', 'skill_type']);
        });
    }
};
