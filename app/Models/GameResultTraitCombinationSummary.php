<?php

namespace App\Models;

class GameResultTraitCombinationSummary extends DynamicModel
{
    protected $guarded = [];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $table = 'game_results_trait_combination_summary';
    protected $primaryKey = 'id';
}