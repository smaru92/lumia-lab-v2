<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VersionHistory extends Model
{
    protected $table = 'version_histories';
    protected $primaryKey = 'id';

    protected $fillable = [
        'version_season',
        'version_major',
        'version_minor',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * 패치노트 관계
     */
    public function patchNotes(): HasMany
    {
        return $this->hasMany(PatchNote::class);
    }

    /**
     * 버전 문자열 반환
     */
    public function getVersionAttribute(): string
    {
        $version = "";
        if ($this->version_season) {
            $version .= "S{$this->version_season} ";
        }
        $version .= "{$this->version_major}.{$this->version_minor}";
        return $version;
    }

    /**
     * 진행 상태 반환
     */
    public function getStatusAttribute(): string
    {
        $now = now();
        $start = \Carbon\Carbon::parse($this->start_date);
        $end = \Carbon\Carbon::parse($this->end_date);

        if ($now->lt($start)) {
            return '예정';
        } elseif ($now->between($start, $end)) {
            return '진행중';
        } else {
            return '종료';
        }
    }
}
