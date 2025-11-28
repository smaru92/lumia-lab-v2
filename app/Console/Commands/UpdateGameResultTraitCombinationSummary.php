<?php

namespace App\Console\Commands;

use App\Services\GameResultTraitCombinationSummaryService;
use Illuminate\Console\Command;

class UpdateGameResultTraitCombinationSummary extends Command
{
    protected $signature = 'update:game-result-trait-combination-summary {version_season?} {version_major?} {version_minor?}';

    protected $description = '캐릭터별 특성 조합 통계 데이터를 갱신합니다.';

    public function handle(GameResultTraitCombinationSummaryService $service)
    {
        $this->info('특성 조합 통계 갱신을 시작합니다...');

        $versionSeason = $this->argument('version_season') ?? null;
        $versionMajor = $this->argument('version_major') ?? null;
        $versionMinor = $this->argument('version_minor') ?? null;

        try {
            $service->updateGameResultTraitCombinationSummary($versionSeason, $versionMajor, $versionMinor);

            $this->info('특성 조합 통계 갱신이 완료되었습니다.');
        } catch (\Exception $e) {
            $this->error('오류 발생: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
