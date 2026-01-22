<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CharacterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'max_hp' => $this->max_hp,
            'max_hp_by_lv' => $this->max_hp_by_lv,
            'max_mp' => $this->max_mp,
            'max_mp_by_lv' => $this->max_mp_by_lv,
            'init_extra_point' => $this->init_extra_point,
            'max_extra_point' => $this->max_extra_point,
            'attack_power' => $this->attack_power,
            'attack_power_by_lv' => $this->attack_power_by_lv,
            'deffence' => $this->deffence,
            'deffence_by_lv' => $this->deffence_by_lv,
            'hp_regen' => $this->hp_regen,
            'hp_regen_by_lv' => $this->hp_regen_by_lv,
            'sp_regen' => $this->sp_regen,
            'sp_regen_by_lv' => $this->sp_regen_by_lv,
            'attack_speed' => $this->attack_speed,
            'attack_speed_limit' => $this->attack_speed_limit,
            'attack_speed_min' => $this->attack_speed_min,
            'move_speed' => $this->move_speed,
            'sight_range' => $this->sight_range,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
