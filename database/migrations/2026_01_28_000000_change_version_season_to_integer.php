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
        $tables = [
            'version_histories',
            'game_results_equipment_main_summary',
            'game_results_equipment_summary',
            'game_results_first_equipment_main_summary',
            'game_results_rank_summary',
            'game_results_summary',
            'game_results_tactical_skill_summary',
            'game_results_trait_summary',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'version_season')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->integer('version_season')->default(1)->nullable()->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'version_histories',
            'game_results_equipment_main_summary',
            'game_results_equipment_summary',
            'game_results_first_equipment_main_summary',
            'game_results_rank_summary',
            'game_results_summary',
            'game_results_tactical_skill_summary',
            'game_results_trait_summary',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'version_season')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->string('version_season')->default(1)->nullable()->change();
                });
            }
        }
    }
};
