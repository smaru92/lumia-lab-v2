<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Character extends Model
{

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    # table 정의
    protected $table = 'characters';
    # primaryKey 정의
    protected $primaryKey = 'id';


    public function getColumns()
    {
        return Schema::getColumnListing('characters');
    }
}
