<?php

namespace App\Console\Commands;

use App\Services\GameResultRankSummaryService;
use Illuminate\Console\Command;

class UpdateGameResultRankSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:game-results-rank-summary {version_season?} {version_major?} {version_minor?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(GameResultRankSummaryService $gameResultRankSummaryService)
    {
        $versionSeason = $this->argument('version_season') ?? null;
        $versionMajor = $this->argument('version_major') ?? null;
        $versionMinor = $this->argument('version_minor') ?? null;
        $gameResultRankSummaryService->updateGameResultRankSummary($versionSeason, $versionMajor, $versionMinor);
    }
}
