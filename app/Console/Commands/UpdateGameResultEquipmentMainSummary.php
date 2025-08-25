<?php

namespace App\Console\Commands;

use App\Models\GameResultEquipmentSummary;
use App\Services\GameResultEquipmentMainSummaryService;
use App\Services\GameResultEquipmentSummaryService;
use Illuminate\Console\Command;

class UpdateGameResultEquipmentMainSummary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:game-results-equipment-main-summary {version_season?} {version_major?} {version_minor?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(GameResultEquipmentMainSummaryService $gameResultEquipmentMainSummaryService)
    {
        $versionSeason = $this->argument('version_season') ?? null;
        $versionMajor = $this->argument('version_major') ?? null;
        $versionMinor = $this->argument('version_minor') ?? null;
        $gameResultEquipmentMainSummaryService->updateGameResultEquipmentMainSummary($versionSeason, $versionMajor, $versionMinor);
    }
}
