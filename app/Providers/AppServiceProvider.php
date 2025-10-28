<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $this->app->make(\App\Console\Kernel::class)->schedule($schedule);
        });

        // 이미지 캐시 버스팅을 위한 헬퍼 함수
        if (!function_exists('image_asset')) {
            function image_asset($path) {
                $version = config('erDev.imageVersion', 'v1');
                return asset($path) . '?v=' . $version;
            }
        }
    }
}
