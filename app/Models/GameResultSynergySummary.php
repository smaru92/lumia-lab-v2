<?php

namespace App\Models;

class GameResultSynergySummary extends DynamicModel
{
    protected $guarded = [];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $table = 'game_results_synergy_summary';
    protected $primaryKey = 'id';
}
