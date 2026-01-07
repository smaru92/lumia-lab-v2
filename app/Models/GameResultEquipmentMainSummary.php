<?php

namespace App\Models;

class GameResultEquipmentMainSummary extends DynamicModel
{
    protected $guarded = [];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $table = 'game_results_equipment_main_summary';
    protected $primaryKey = 'id';
}
