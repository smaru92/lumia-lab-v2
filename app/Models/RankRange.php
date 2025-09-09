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

    protected $fillable = [
        'name',
        'grade1',
        'grade2',
        'min_score',
        'max_score',
        'version_season',
        'version_major',
        'version_minor'
    ];
}
