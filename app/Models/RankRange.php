<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RankRange extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'rank_ranges';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
}
