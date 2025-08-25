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
        Schema::create('equipments', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->string('name')->nullable();
            $table->string('item_type1')->nullable();
            $table->string('item_type2')->nullable();
            $table->string('item_grade')->nullable();
            $table->decimal('attack_power', 10, 3)->nullable();
            $table->decimal('attack_power_by_lv', 10, 3)->nullable();
            $table->decimal('defense', 10, 3)->nullable();
            $table->decimal('defense_by_lv', 10, 3)->nullable();
            $table->decimal('skill_amp', 10, 3)->nullable();
            $table->decimal('skill_amp_by_lv', 10, 3)->nullable();
            $table->decimal('skill_amp_ratio', 10, 3)->nullable();
            $table->decimal('skill_amp_ratio_by_lv', 10, 3)->nullable();
            $table->decimal('adaptive_force', 10, 3)->nullable();
            $table->decimal('adaptive_force_by_lv', 10, 3)->nullable();
            $table->decimal('max_hp', 10, 3)->nullable();
            $table->decimal('max_hp_by_lv', 10, 3)->nullable();
            $table->decimal('mas_sp', 10, 3)->nullable();
            $table->decimal('mas_sp_by_lv', 10, 3)->nullable();
            $table->decimal('hp_regen', 10, 3)->nullable();
            $table->decimal('hp_regen_ratio', 10, 3)->nullable();
            $table->decimal('sp_regen', 10, 3)->nullable();
            $table->decimal('sp_regen_ratio', 10, 3)->nullable();
            $table->decimal('attack_speed_ratio', 10, 3)->nullable();
            $table->decimal('attack_speed_ratio_by_lv', 10, 3)->nullable();
            $table->decimal('critical_strike_chance', 10, 3)->nullable();
            $table->decimal('critical_strike_damage', 10, 3)->nullable();
            $table->decimal('prevent_critical_strike_damaged', 10, 3)->nullable();
            $table->decimal('cooldown_reduction', 10, 3)->nullable();
            $table->decimal('cooldown_limit', 10, 3)->nullable();
            $table->decimal('life_steal', 10, 3)->nullable();
            $table->decimal('normal_life_steal', 10, 3)->nullable();
            $table->decimal('skill_life_steal', 10, 3)->nullable();
            $table->decimal('move_speed', 10, 3)->nullable();
            $table->decimal('move_speed_ratio', 10, 3)->nullable();
            $table->decimal('move_speed_out_of_combat', 10, 3)->nullable();
            $table->decimal('sight_range', 10, 3)->nullable();
            $table->decimal('attack_range', 10, 3)->nullable();
            $table->decimal('increase_basic_attack_damage', 10, 3)->nullable();
            $table->decimal('increase_basic_attack_damage_by_lv', 10, 3)->nullable();
            $table->decimal('increase_basic_attack_damage_ratio', 10, 3)->nullable();
            $table->decimal('increase_basic_attack_damage_ratio_by_lv', 10, 3)->nullable();
            $table->decimal('prevent_basic_attack_damaged', 10, 3)->nullable();
            $table->decimal('prevent_basic_attack_damaged_by_lv', 10, 3)->nullable();
            $table->decimal('prevent_basic_attack_damaged_ratio', 10, 3)->nullable();
            $table->decimal('prevent_basic_attack_damaged_ratio_by_lv', 10, 3)->nullable();
            $table->decimal('prevent_skill_damaged', 10, 3)->nullable();
            $table->decimal('prevent_skill_damaged_by_lv', 10, 3)->nullable();
            $table->decimal('prevent_skill_damaged_ratio', 10, 3)->nullable();
            $table->decimal('prevent_skill_damaged_ratio_by_lv', 10, 3)->nullable();
            $table->decimal('penetration_defense', 10, 3)->nullable();
            $table->decimal('penetration_defense_ratio', 10, 3)->nullable();
            $table->decimal('trap_damage_reduce', 10, 3)->nullable();
            $table->decimal('trap_damage_reduce_ratio', 10, 3)->nullable();
            $table->decimal('slow_resist_ratio', 10, 3)->nullable();
            $table->decimal('hp_healed_increase_ratio', 10, 3)->nullable();
            $table->decimal('healer_give_hp_heal_ratio', 10, 3)->nullable();
            $table->decimal('unique_attack_range', 10, 3)->nullable();
            $table->decimal('unique_hp_healed_increase_ratio', 10, 3)->nullable();
            $table->decimal('unique_cooldown_limit', 10, 3)->nullable();
            $table->decimal('unique_tenacity', 10, 3)->nullable();
            $table->decimal('unique_move_speed', 10, 3)->nullable();
            $table->decimal('unique_penetration_defense', 10, 3)->nullable();
            $table->decimal('unique_penetration_defense_ratio', 10, 3)->nullable();
            $table->decimal('unique_life_steal', 10, 3)->nullable();
            $table->decimal('unique_skill_amp_ratio', 10, 3)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
