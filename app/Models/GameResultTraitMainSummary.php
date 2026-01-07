<?php

namespace App\Models;

class GameResultTraitMainSummary extends DynamicModel
{
    protected $guarded = [];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $table = 'game_results_trait_main_summary';
    protected $primaryKey = 'id';

    /**
     * 특성 정보와의 관계
     */
    public function trait()
    {
        return $this->belongsTo(GameTrait::class, 'trait_id', 'id');
    }
}