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
        Schema::table('game_result_trait_orders', function (Blueprint $table) {
            $table->index('game_result_id', 'trait_orders_game_result_id_idx');
            $table->index(['game_result_id', 'trait_id'], 'trait_orders_game_result_trait_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_result_trait_orders', function (Blueprint $table) {
            $table->dropIndex('trait_orders_game_result_id_idx');
            $table->dropIndex('trait_orders_game_result_trait_idx');
        });
    }
};