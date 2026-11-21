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
        'version_hotfix',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'version_season' => 'integer',
        'version_major' => 'integer',
        'version_minor' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    /**
     * 현재 시점에서 시작된 버전만 조회 (미래 버전 제외)
     */
    public function scopeActive($query)
    {
        return $query->where('start_date', '<=', now());
    }

    /**
     * 버전 번호 기준 최신순 (12.2.0b > 12.2.0 > 12.1.0)
     *
     * created_at 으로 최신을 고르면 구버전을 나중에 등록했을 때 그 구버전이 "최신"이 되어
     * 수집기가 현재 버전 행을 또 만들고(중복), 집계 명령어도 엉뚱한 버전을 돌린다.
     * MySQL 은 DESC 정렬에서 NULL 이 맨 뒤라 핫픽스 행이 같은 버전의 기본 행보다 앞에 온다.
     */
    public function scopeNewestVersionFirst($query)
    {
        return $query->orderByDesc('version_season')
            ->orderByDesc('version_major')
            ->orderByDesc('version_minor')
            ->orderByDesc('version_hotfix');
    }

    /**
     * 패치노트 관계
     */
    public function patchNotes(): HasMany
    {
        return $this->hasMany(PatchNote::class);
    }

    /**
     * 집계/URL 에 사용하는 버전 키 (핫픽스 포함, 예: 12.2.0b)
     *
     * 핫픽스는 별도 통계로 분리되므로 키 자체에 포함된다.
     * 이 키가 곧 버전별 테이블 접미사(game_results_v12_2_0b)가 된다.
     */
    public function getVersionKeyAttribute(): string
    {
        return "{$this->version_season}.{$this->version_major}.{$this->version_minor}" . ($this->version_hotfix ?? '');
    }

    /**
     * 화면 표기용 버전 (키와 동일)
     */
    public function getDisplayVersionAttribute(): string
    {
        return $this->version_key;
    }

    /**
     * 버전별 테이블/집계에 넘기는 필터 배열
     */
    public function getVersionFiltersAttribute(): array
    {
        return [
            'version_season' => $this->version_season,
            'version_major' => $this->version_major,
            'version_minor' => $this->version_minor,
            'version_hotfix' => $this->version_hotfix,
        ];
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
        $version .= $this->version_hotfix ?? '';
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
