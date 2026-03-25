<?php

namespace App\Console\Commands;

use App\Services\GameResultSynergySummaryService;
use Illuminate\Console\Command;

class UpdateGameResultSynergySummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:game-results-synergy-summary {version_season?} {version_major?} {version_minor?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '캐릭터 시너지 통계 업데이트';

    /**
     * Execute the console command.
     */
    public function handle(GameResultSynergySummaryService $gameResultSynergySummaryService)
    {
        $versionSeason = $this->argument('version_season') ?? null;
        $versionMajor = $this->argument('version_major') ?? null;
        $versionMinor = $this->argument('version_minor') ?? null;
        $gameResultSynergySummaryService->updateGameResultSynergySummary($versionSeason, $versionMajor, $versionMinor);
    }
}
