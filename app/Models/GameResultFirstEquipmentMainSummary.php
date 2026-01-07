<?php

namespace App\Models;

class GameResultFirstEquipmentMainSummary extends DynamicModel
{
    protected $guarded = [];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $table = 'game_results_first_equipment_main_summary';
    protected $primaryKey = 'id';
}
