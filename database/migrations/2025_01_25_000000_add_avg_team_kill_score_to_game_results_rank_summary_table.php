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
        Schema::table('game_results_rank_summary', function (Blueprint $table) {
            $table->decimal('avg_team_kill_score', 10, 3)->nullable()->after('avg_mmr_gain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_results_rank_summary', function (Blueprint $table) {
            $table->dropColumn('avg_team_kill_score');
        });
    }
};
