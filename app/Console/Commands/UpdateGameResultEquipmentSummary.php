<?php

namespace App\Console\Commands;

use App\Models\GameResultEquipmentSummary;
use App\Services\GameResultEquipmentSummaryService;
use Illuminate\Console\Command;

class UpdateGameResultEquipmentSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:game-results-equipment-summary {version_season?} {version_major?} {version_minor?} {version_hotfix?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(GameResultEquipmentSummaryService $gameResultSummaryService)
    {
        $versionSeason = $this->argument('version_season') ?? null;
        $versionMajor = $this->argument('version_major') ?? null;
        $versionMinor = $this->argument('version_minor') ?? null;
        $versionHotfix = $this->argument('version_hotfix') ?? null;
        $gameResultSummaryService->updateGameResultEquipmentSummary($versionSeason, $versionMajor, $versionMinor, $versionHotfix);
    }
}
