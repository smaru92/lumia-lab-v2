<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameTrait extends Model
{
    protected $table = 'traits';
    protected $primaryKey = 'id';

    protected $guarded = [];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
}
