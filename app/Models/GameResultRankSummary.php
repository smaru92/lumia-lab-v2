<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameResultRankSummary extends Model
{
    protected $guarded = [

    ];
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    # table 정의
    protected $table = 'game_results_rank_summary';
    # primaryKey 정의
    protected $primaryKey = 'id';
}
