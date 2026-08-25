<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 특성 그룹(메인/서브1/서브2)의 버전별 변경 이력
 */
class TraitVersionGroup extends Model
{
    protected $table = 'trait_version_groups';
    protected $primaryKey = 'id';

    protected $fillable = [
        'version_season',
        'version_major',
        'version_minor',
        'trait_id',
        'trait_group',
    ];

    public function gameTrait()
    {
        return $this->belongsTo(GameTrait::class, 'trait_id', 'id');
    }
}
