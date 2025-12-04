<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EquipmentSkill extends Model
{
    protected $table = 'equipment_skills';
    protected $primaryKey = 'id';
    public $incrementing = true;

    protected $fillable = [
        'name',
        'grade',
        'sub_category',
        'description',
    ];

    /**
     * 장비들과의 관계 (다대다)
     */
    public function equipments(): BelongsToMany
    {
        return $this->belongsToMany(Equipment::class, 'equipment_equipment_skill', 'equipment_skill_id', 'equipment_id')
            ->withTimestamps();
    }

    /**
     * Filament을 위한 역관계 별칭
     */
    public function equipment(): BelongsToMany
    {
        return $this->equipments();
    }
}
