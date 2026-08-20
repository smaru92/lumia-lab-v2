<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class VersionHistory extends Model
{
    protected $table = 'version_histories';
    protected $primaryKey = 'id';

    /** 핫픽스 표기 맵 캐시 키 */
    public const HOTFIX_MAP_CACHE_KEY = 'version_histories_hotfix_map';

    protected $fillable = [
        'version_season',
        'version_major',
        'version_minor',
        'version_hotfix',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    protected static function booted(): void
    {
        // 핫픽스 값이 바뀌면 표기 맵 캐시를 즉시 갱신한다.
        static::saved(fn () => Cache::forget(self::HOTFIX_MAP_CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::HOTFIX_MAP_CACHE_KEY));
    }

    /**
     * 현재 시점에서 시작된 버전만 조회 (미래 버전 제외)
     */
    public function scopeActive($query)
    {
        return $query->where('start_date', '<=', now());
    }

    /**
     * 패치노트 관계
     */
    public function patchNotes(): HasMany
    {
        return $this->hasMany(PatchNote::class);
    }

    /**
     * 집계/URL 에 사용하는 버전 키 (핫픽스 미포함)
     */
    public function getVersionKeyAttribute(): string
    {
        return "{$this->version_season}.{$this->version_major}.{$this->version_minor}";
    }

    /**
     * 화면 표기용 버전 (핫픽스 포함, 예: 12.1.1a)
     */
    public function getDisplayVersionAttribute(): string
    {
        return $this->version_key . ($this->version_hotfix ?? '');
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

    /**
     * 버전 키("12.1.1") => 핫픽스 알파벳("a") 맵
     *
     * 페이지 데이터 캐시에는 핫픽스가 적용되기 전 모델이 들어있을 수 있으므로,
     * 표기는 항상 이 맵을 통해 최신 값으로 조회한다.
     */
    public static function hotfixMap(): array
    {
        return Cache::rememberForever(self::HOTFIX_MAP_CACHE_KEY, function () {
            // 마이그레이션 이전 환경에서도 화면이 깨지지 않도록 방어
            if (!Schema::hasColumn((new static)->getTable(), 'version_hotfix')) {
                return [];
            }

            return static::query()
                ->whereNotNull('version_hotfix')
                ->where('version_hotfix', '!=', '')
                ->get(['version_season', 'version_major', 'version_minor', 'version_hotfix'])
                ->mapWithKeys(fn ($v) => [$v->version_key => $v->version_hotfix])
                ->all();
        });
    }

    /**
     * 버전 키에 핫픽스 알파벳을 붙인 표기 문자열 반환
     */
    public static function displayVersion(?string $versionKey): string
    {
        if (!$versionKey) {
            return '';
        }

        return $versionKey . (static::hotfixMap()[$versionKey] ?? '');
    }
}
