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
        // withoutOverlapping() 의 만료 시간(분)은 "실측 최대 소요 x 약 3배" 로 잡는다.
        // 기본값 1440분(24시간)이면, 작업이 락을 반납하지 못한 채 죽었을 때
        // 그 작업이 하루 내내 한 번도 실행되지 않는다.
        // 2026-09-24 16:37 DB 를 수동 재시작했을 때 실제로 발생했다.
        // (cache_locks 삭제 쿼리가 Connection refused 로 실패 -> synergy /
        //  equipment-main / first-equipment-main 3개가 34시간 동안 멈춤)
        // 만료를 짧게 두면 같은 일이 생겨도 다음 주기에 스스로 복구된다.
        // 실측 최대(2026-09-23~25): fetch 77s / summary 99s / tactical 97s /
        //   rank 76s / trait 223s / first-equip-main 318s / trait-main 337s /
        //   equip-summary 426s / synergy 520s / trait-combination 524s /
        //   equipment-main 542s
        // 주의: 만료가 실제 소요보다 짧으면 중복 실행이 되므로, 소요 시간이
        //   늘어나면(특히 equipment-main 은 하루 1분씩 증가 중) 같이 올려야 한다.
        // $schedule->command('inspire')->hourly();
        $schedule->command('fetch:game-results')->everyMinute()->withoutOverlapping(5)->runInBackground();

        // 메인페이지 데이터 - 1시간마다 (매시 정각)
        $schedule->command('update:game-results-summary')->cron('0 * * * *')->withoutOverlapping(10)->runInBackground();

        // 캐릭터 지표 추이용 일자별 스냅샷 (하루 1건, 집계가 한 바퀴 돈 뒤인 새벽에)
        // 누적·일일 지표를 함께 남긴다 (요약 테이블에는 일일 지표가 없어 원본에서 다시 집계)
        $schedule->call(function () {
            $version = \App\Models\VersionHistory::active()->latest('created_at')->first();
            if ($version) {
                \Illuminate\Support\Facades\Artisan::call('snapshot:backfill-daily', [
                    'version' => $version->version_key,
                    '--date' => now()->subDay()->toDateString(),
                ]);
            }
        })->dailyAt('04:05')->name('snapshot-summary-daily')->withoutOverlapping(60);

        // 나머지 명령어들 - 2시간마다, 서로 최소 10분 이상 간격으로 분산
        // 전술스킬 데이터 - 짝수 시간 10분
        $schedule->command('update:game-results-tactical-skill-summary')->cron('10 */2 * * *')->withoutOverlapping(10)->runInBackground();
        // 장비 메인 데이터 - 짝수 시간 20분
        $schedule->command('update:game-results-equipment-main-summary')->cron('20 */2 * * *')->withoutOverlapping(30)->runInBackground();
        // 초반 장비 메인 데이터 - 짝수 시간 30분
        $schedule->command('update:game-results-first-equipment-main-summary')->cron('30 */2 * * *')->withoutOverlapping(20)->runInBackground();
        // 캐릭터별/순위별 데이터 - 짝수 시간 40분
        $schedule->command('update:game-results-rank-summary')->cron('40 */2 * * *')->withoutOverlapping(10)->runInBackground();
        // 캐릭터별/특성/순위별 데이터 - 짝수 시간 50분
        $schedule->command('update:game-results-trait-summary')->cron('50 */2 * * *')->withoutOverlapping(15)->runInBackground();
        // 캐릭터별/장비별/순위별 데이터 - 홀수 시간 10분
        $schedule->command('update:game-results-equipment-summary')->cron('10 1-23/2 * * *')->withoutOverlapping(25)->runInBackground();
        // 캐릭터별 시너지 데이터 - 홀수 시간 20분
        $schedule->command('update:game-results-synergy-summary')->cron('20 1-23/2 * * *')->withoutOverlapping(30)->runInBackground();
        // 캐릭터별/특성조합별 데이터 - 홀수 시간 30분
        $schedule->command('update:game-result-trait-combination-summary')->cron('30 1-23/2 * * *')->withoutOverlapping(30)->runInBackground();
        // 특성 메인 데이터 - 홀수 시간 50분
        $schedule->command('update:game-results-trait-main-summary')->cron('50 1-23/2 * * *')->withoutOverlapping(20)->runInBackground();
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
