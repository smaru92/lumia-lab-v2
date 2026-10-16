<?php

namespace App\Console\Commands;

use App\Services\GameResultSkillOrderSummaryService;
use Illuminate\Console\Command;

class UpdateGameResultSkillOrderSummary extends Command
{
    protected $signature = 'update:game-results-skill-order-summary {version_season?} {version_major?} {version_minor?} {version_hotfix?}
                            {--rebuild : 게임별 스킬 순서 테이블을 비우고 처음부터 다시 만든다}';

    protected $description = '캐릭터별 스킬 마스터 순서 통계 데이터를 갱신합니다.';

    public function handle(GameResultSkillOrderSummaryService $service)
    {
        $this->info('스킬 순서 통계 갱신을 시작합니다...');

        $versionSeason = $this->argument('version_season') ?? null;
        $versionMajor = $this->argument('version_major') ?? null;
        $versionMinor = $this->argument('version_minor') ?? null;
        $versionHotfix = $this->argument('version_hotfix') ?? null;

        try {
            $service->updateGameResultSkillOrderSummary(
                $versionSeason,
                $versionMajor,
                $versionMinor,
                $versionHotfix,
                (bool) $this->option('rebuild')
            );

            $this->info('스킬 순서 통계 갱신이 완료되었습니다.');
        } catch (\Exception $e) {
            $this->error('오류 발생: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
