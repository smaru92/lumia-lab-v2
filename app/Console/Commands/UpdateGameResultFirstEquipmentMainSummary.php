<?php

namespace App\Console\Commands;

use App\Services\GameResultFirstEquipmentMainSummaryService;
use Illuminate\Console\Command;

class UpdateGameResultFirstEquipmentMainSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:game-results-first-equipment-main-summary {version_season?} {version_major?} {version_minor?} {version_hotfix?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(GameResultFirstEquipmentMainSummaryService $gameResultEquipmentMainSummaryService)
    {
        $versionSeason = $this->argument('version_season') ?? null;
        $versionMajor = $this->argument('version_major') ?? null;
        $versionMinor = $this->argument('version_minor') ?? null;
        $versionHotfix = $this->argument('version_hotfix') ?? null;
        $gameResultEquipmentMainSummaryService->updateGameResultFirstEquipmentMainSummary($versionSeason, $versionMajor, $versionMinor, $versionHotfix);
    }
}
