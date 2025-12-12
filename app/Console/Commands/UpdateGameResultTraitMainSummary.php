<?php

namespace App\Console\Commands;

use App\Services\GameResultTraitMainSummaryService;
use Illuminate\Console\Command;

class UpdateGameResultTraitMainSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:game-results-trait-main-summary {version_season?} {version_major?} {version_minor?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '특성 메인 통계 요약 데이터 갱신';

    /**
     * Execute the console command.
     */
    public function handle(GameResultTraitMainSummaryService $gameResultTraitMainSummaryService)
    {
        $versionSeason = $this->argument('version_season') ?? null;
        $versionMajor = $this->argument('version_major') ?? null;
        $versionMinor = $this->argument('version_minor') ?? null;
        $gameResultTraitMainSummaryService->updateGameResultTraitMainSummary($versionSeason, $versionMajor, $versionMinor);
    }
}
