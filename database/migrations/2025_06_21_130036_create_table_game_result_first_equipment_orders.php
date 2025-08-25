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
        Schema::create('game_result_first_equipment_orders', function (Blueprint $table) {
            $table->id();
            $table->integer('game_result_id')->comment('게임 id');
            $table->integer('equipment_id')->comment('장비 id');
            $table->timestamp('created_at')->nullable();

            $table->index(['equipment_id', 'game_result_id'], 'idx_gre_equip_result');
            $table->index(['game_result_id'], 'idx_gre_game_result_id');
            $table->index(['game_result_id', 'equipment_id'], 'idx_gre_result_equip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_game_result_first_equipment_orders');
    }
};
