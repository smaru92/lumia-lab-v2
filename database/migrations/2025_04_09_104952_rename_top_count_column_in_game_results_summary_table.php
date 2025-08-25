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
        Schema::table('game_results_summary', function (Blueprint $table) {
            $table->renameColumn('top3_count', 'top2_count');
            $table->renameColumn('top5_count', 'top4_count');
            $table->renameColumn('top3_count_percent', 'top2_count_percent');
            $table->renameColumn('top5_count_percent', 'top4_count_percent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_results_summary', function (Blueprint $table) {
            //
        });
    }
};
