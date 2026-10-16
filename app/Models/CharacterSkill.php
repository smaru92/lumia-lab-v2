<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CharacterSkill extends Model
{
    protected $table = 'character_skills';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $guarded = [];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
}
