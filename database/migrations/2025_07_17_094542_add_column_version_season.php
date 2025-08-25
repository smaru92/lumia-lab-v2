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
        Schema::table('version_histories', function (Blueprint $table) {
            $table->string('version_season')->default(1)->nullable()->before('version_major');
        });
        Schema::table('game_results_equipment_main_summary', function (Blueprint $table) {
            $table->string('version_season')->default(1)->nullable()->before('version_major');
        });
        Schema::table('game_results_equipment_summary', function (Blueprint $table) {
            $table->string('version_season')->default(1)->nullable()->before('version_major');
        });
        Schema::table('game_results_first_equipment_main_summary', function (Blueprint $table) {
            $table->string('version_season')->default(1)->nullable()->before('version_major');
        });
        Schema::table('game_results_rank_summary', function (Blueprint $table) {
            $table->string('version_season')->default(1)->nullable()->before('version_major');
        });
        Schema::table('game_results_summary', function (Blueprint $table) {
            $table->string('version_season')->default(1)->nullable()->before('version_major');
        });
        Schema::table('game_results_tactical_skill_summary', function (Blueprint $table) {
            $table->string('version_season')->default(1)->nullable()->before('version_major');
        });
        Schema::table('game_results_trait_summary', function (Blueprint $table) {
            $table->string('version_season')->default(1)->nullable()->before('version_major');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
