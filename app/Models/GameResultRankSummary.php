<?php

namespace App\Models;

class GameResultRankSummary extends DynamicModel
{
    protected $guarded = [];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $table = 'game_results_rank_summary';
    protected $primaryKey = 'id';
}
