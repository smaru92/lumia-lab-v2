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

        // helpers.php 파일 수동 로드 (composer autoload 백업)
        $helpersPath = app_path('helpers.php');
        if (file_exists($helpersPath)) {
            require_once $helpersPath;
        }
    }
}
