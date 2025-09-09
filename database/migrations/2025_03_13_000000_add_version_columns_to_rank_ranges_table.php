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
        Schema::table('rank_ranges', function (Blueprint $table) {
            $table->string('version_season')->nullable()->after('id')->comment('게임 시즌 버전 (NULL=기본값)')->index();
            $table->integer('version_major')->nullable()->after('version_season')->comment('게임 메이저 버전 (NULL=기본값)')->index();
            $table->integer('version_minor')->nullable()->after('version_major')->comment('게임 마이너 버전 (NULL=기본값)')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rank_ranges', function (Blueprint $table) {
            $table->dropColumn(['version_season', 'version_major', 'version_minor']);
        });
    }
};