<?php

namespace App\Models;

class GameResultTacticalSkillSummary extends DynamicModel
{
    protected $guarded = [];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $table = 'game_results_tactical_skill_summary';
    protected $primaryKey = 'id';
}
