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
    public function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('fetch:game-results')->everyMinute()->withoutOverlapping()->runInBackground();

        // 메인페이지 데이터 - 1시간마다 (매시 정각)
        $schedule->command('update:game-results-summary')->cron('0 * * * *')->withoutOverlapping()->runInBackground();

        // 나머지 명령어들 - 2시간마다, 서로 최소 10분 이상 간격으로 분산
        // 전술스킬 데이터 - 짝수 시간 10분
        $schedule->command('update:game-results-tactical-skill-summary')->cron('10 */2 * * *')->withoutOverlapping()->runInBackground();
        // 장비 메인 데이터 - 짝수 시간 20분
        $schedule->command('update:game-results-equipment-main-summary')->cron('20 */2 * * *')->withoutOverlapping()->runInBackground();
        // 초반 장비 메인 데이터 - 짝수 시간 30분
        $schedule->command('update:game-results-first-equipment-main-summary')->cron('30 */2 * * *')->withoutOverlapping()->runInBackground();
        // 캐릭터별/순위별 데이터 - 짝수 시간 40분
        $schedule->command('update:game-results-rank-summary')->cron('40 */2 * * *')->withoutOverlapping()->runInBackground();
        // 캐릭터별/특성/순위별 데이터 - 짝수 시간 50분
        $schedule->command('update:game-results-trait-summary')->cron('50 */2 * * *')->withoutOverlapping()->runInBackground();
        // 캐릭터별/장비별/순위별 데이터 - 홀수 시간 10분
        $schedule->command('update:game-results-equipment-summary')->cron('10 1-23/2 * * *')->withoutOverlapping()->runInBackground();
        // 캐릭터별/특성조합별 데이터 - 홀수 시간 30분
        $schedule->command('update:game-result-trait-combination-summary')->cron('30 1-23/2 * * *')->withoutOverlapping()->runInBackground();
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
