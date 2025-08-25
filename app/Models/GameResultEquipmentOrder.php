<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameResultEquipmentOrder extends DynamicModel
{
    protected $guarded = [

    ];
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    # table 정의
    protected $table = 'game_result_equipment_orders';
    # primaryKey 정의
    protected $primaryKey = 'id';
}
