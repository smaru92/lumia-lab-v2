<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Equipment extends Model
{
    protected $guarded = [

    ];
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    # table 정의
    protected $table = 'equipments';
    # primaryKey 정의
    protected $primaryKey = 'id';

    /**
     * 장비 스킬들과의 관계 (다대다)
     */
    public function equipmentSkills(): BelongsToMany
    {
        return $this->belongsToMany(EquipmentSkill::class, 'equipment_equipment_skill', 'equipment_id', 'equipment_skill_id')
            ->withTimestamps();
    }
}
