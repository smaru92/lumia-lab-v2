<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('fetch:game-results')->everyMinute();
        // 메인페이지&전술스킬 데이터
        $schedule->command('update:game-results-summary')->cron('0 * * * *')->withoutOverlapping();
        $schedule->command('update:game-results-tactical-skill-summary')->cron('0 * * * *')->withoutOverlapping();
        $schedule->command('update:game-results-equipment-main-summary')->cron('10 * * * *')->withoutOverlapping();
        $schedule->command('update:game-results-first-equipment-main-summary')->cron('20 * * * *')->withoutOverlapping();
        // 캐릭터별/순위별 데이터
        $schedule->command('update:game-results-rank-summary')->cron('30 * * * *')->withoutOverlapping();
        // 캐릭터별/특성/순위별 데이터
        $schedule->command('update:game-results-trait-summary')->cron('40 * * * *')->withoutOverlapping();
        // 캐릭터별/장비별/순위별 데이터
        $schedule->command('update:game-results-equipment-summary')->cron('50 * * * *')->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
