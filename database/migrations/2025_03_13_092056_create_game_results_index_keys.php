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
        Schema::table('game_results', function (Blueprint $table) {
            $table->index(['character_id', 'weapon_id', 'game_rank', 'mmr_gain'], 'idx_game_results_character_weapon_rank');
        });

        Schema::table('equipments', function (Blueprint $table) {
            $table->index('id', 'idx_equipments_id');
        });

        Schema::table('characters', function (Blueprint $table) {
            $table->index('id', 'idx_characters_id');
        });
    }

    public function down(): void {
        Schema::table('game_results', function (Blueprint $table) {
            $table->dropIndex('idx_game_results_character_weapon_rank');
        });

        Schema::table('equipments', function (Blueprint $table) {
            $table->dropIndex('idx_equipments_id');
        });

        Schema::table('characters', function (Blueprint $table) {
            $table->dropIndex('idx_characters_id');
        });
    }
};
