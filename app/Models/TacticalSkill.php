<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TacticalSkill extends Model
{
    protected $table = 'tactical_skills';
    protected $primaryKey = 'id';

    protected $guarded = [];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
}
