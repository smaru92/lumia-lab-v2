<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * 사이트 운영 설정 (key-value)
 */
class Setting extends Model
{
    protected $table = 'settings';
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    public const CACHE_KEY = 'site_settings_all';

    protected $fillable = ['key', 'value'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * 전체 설정을 [key => value] 로 반환 (캐시됨)
     */
    public static function all_values(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            // 마이그레이션 이전 환경에서도 화면이 깨지지 않도록 방어
            if (!\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                return [];
            }

            return static::query()->pluck('value', 'key')->all();
        });
    }
}
