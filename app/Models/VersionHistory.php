<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VersionHistory extends Model
{

    # table 정의
    protected $guarded = [

    ];
    protected $table = 'version_histories';
    # primaryKey 정의
    protected $primaryKey = 'id';
}
