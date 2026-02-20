<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\EquipmentResource;
use App\Models\Equipment;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    public function index()
    {
        $equipment = Equipment::orderBy('name')->get();
        return EquipmentResource::collection($equipment);
    }

    public function show(Equipment $equipment)
    {
        $equipment->load('equipmentSkills');
        return new EquipmentResource($equipment);
    }

    public function update(Request $request, Equipment $equipment)
    {
        $validated = $request->validate([
            'name' => 'nullable|string',
            'item_type1' => 'nullable|string',
            'item_type2' => 'nullable|string',
            'item_grade' => 'nullable|string',
            'item_type3' => 'nullable|string|in:mt,tl,ml,fc,vf',
            'attack_power' => 'nullable|numeric',
            'attack_power_by_lv' => 'nullable|numeric',
            'defense' => 'nullable|numeric',
            'defense_by_lv' => 'nullable|numeric',
            'skill_amp' => 'nullable|numeric',
            'skill_amp_by_level' => 'nullable|numeric',
            'skill_amp_ratio' => 'nullable|numeric',
            'skill_amp_ratio_by_level' => 'nullable|numeric',
            'adaptive_force' => 'nullable|numeric',
            'adaptive_force_by_level' => 'nullable|numeric',
            'max_hp' => 'nullable|numeric',
            'max_hp_by_lv' => 'nullable|numeric',
            'max_sp' => 'nullable|numeric',
            'max_sp_by_lv' => 'nullable|numeric',
            'hp_regen' => 'nullable|numeric',
            'hp_regen_ratio' => 'nullable|numeric',
            'sp_regen' => 'nullable|numeric',
            'sp_regen_ratio' => 'nullable|numeric',
            'attack_speed_ratio' => 'nullable|numeric',
            'attack_speed_ratio_by_lv' => 'nullable|numeric',
            'critical_strike_chance' => 'nullable|numeric',
            'critical_strike_damage' => 'nullable|numeric',
            'prevent_critical_strike_damaged' => 'nullable|numeric',
            'cooldown_reduction' => 'nullable|numeric',
            'cooldown_limit' => 'nullable|numeric',
            'life_steal' => 'nullable|numeric',
            'normal_life_steal' => 'nullable|numeric',
            'skill_life_steal' => 'nullable|numeric',
            'move_speed' => 'nullable|numeric',
            'move_speed_ratio' => 'nullable|numeric',
            'move_speed_out_of_combat' => 'nullable|numeric',
            'sight_range' => 'nullable|numeric',
            'attack_range' => 'nullable|numeric',
            'increase_basic_attack_damage' => 'nullable|numeric',
            'increase_basic_attack_damage_by_lv' => 'nullable|numeric',
            'increase_basic_attack_damage_ratio' => 'nullable|numeric',
            'increase_basic_attack_damage_ratio_by_lv' => 'nullable|numeric',
            'prevent_basic_attack_damaged' => 'nullable|numeric',
            'prevent_basic_attack_damaged_by_lv' => 'nullable|numeric',
            'prevent_basic_attack_damaged_ratio' => 'nullable|numeric',
            'prevent_basic_attack_damaged_ratio_by_lv' => 'nullable|numeric',
            'prevent_skill_damaged' => 'nullable|numeric',
            'prevent_skill_damaged_by_lv' => 'nullable|numeric',
            'prevent_skill_damaged_ratio' => 'nullable|numeric',
            'prevent_skill_damaged_ratio_by_lv' => 'nullable|numeric',
            'penetration_defense' => 'nullable|numeric',
            'penetration_defense_ratio' => 'nullable|numeric',
            'trap_damage_reduce' => 'nullable|numeric',
            'trap_damage_reduce_ratio' => 'nullable|numeric',
            'slow_resist_ratio' => 'nullable|numeric',
            'hp_healed_increase_ratio' => 'nullable|numeric',
            'healer_give_hp_heal_ratio' => 'nullable|numeric',
            'unique_attack_range' => 'nullable|numeric',
            'unique_hp_healed_increase_ratio' => 'nullable|numeric',
            'unique_cooldown_limit' => 'nullable|numeric',
            'unique_tenacity' => 'nullable|numeric',
            'unique_move_speed' => 'nullable|numeric',
            'unique_penetration_defense' => 'nullable|numeric',
            'unique_penetration_defense_ratio' => 'nullable|numeric',
            'unique_life_steal' => 'nullable|numeric',
            'unique_skill_amp_ratio' => 'nullable|numeric',
        ]);

        $equipment->update($validated);

        return new EquipmentResource($equipment);
    }

    public function syncSkills(Request $request, Equipment $equipment)
    {
        $validated = $request->validate([
            'skill_ids' => 'array',
            'skill_ids.*' => 'exists:equipment_skills,id',
        ]);

        $equipment->equipmentSkills()->sync($validated['skill_ids'] ?? []);

        $equipment->load('equipmentSkills');
        return new EquipmentResource($equipment);
    }
}
