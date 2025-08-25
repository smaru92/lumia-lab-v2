<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DynamicModel extends Model
{
    public function setTableName(string $tableName): void
    {
        $this->setTable($tableName);
    }

    public static function withTable(string $tableName): self
    {
        $instance = new static();
        $instance->setTable($tableName);
        return $instance;
    }
}
