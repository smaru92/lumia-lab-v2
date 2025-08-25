<?php

namespace App\Http\Controllers;

use App\Services\EquipmentMainService;
use Illuminate\Http\Request;

class EquipmentController
{
    protected EquipmentMainService $equipmentMainService;
    protected int $versionSeason;
    protected int $versionMajor;
    protected int $versionMinor;
    protected string $minTier;

    public function __construct(EquipmentMainService $equipmentMainService)
    {
        $this->equipmentMainService = $equipmentMainService;
        $this->equipmentMainService->getLatestVersion();
        $this->versionSeason = 0;
        $this->versionMajor = 0;
        $this->versionMinor = 0;
        $this->minTier = 'diamond';
    }

    public function index(Request $request)
    {
        $defaultTier = config('erDev.defaultTier');
        $defaultVersion = config('erDev.defaultVersion');
        $topRankScore = config('erDev.topRankScore');
        $minTier = $request->input('min_tier', $defaultTier);
        $version = $request->input('version', $defaultVersion);
        $version =  explode('.', $version);
        $versionSeason = $version[0];
        $versionMajor = $version[1];
        $versionMinor = $version[2];
        $filters = [
            'version_season' => $versionSeason,
            'version_major' => $versionMajor,
            'version_minor' => $versionMinor,
            'min_tier' => $minTier,
        ];
        $lastData = $this->equipmentMainService->getGameResultEquipmentMainSummary($filters);
        if ($lastData->first()) {
            $lastUpdate = $lastData->first()->created_at ?? null;
        } else {
            $lastUpdate = null;
        }

        $versions = $this->equipmentMainService->getLatestVersionList();

        $data = [
            'lastUpdate' => $lastUpdate,
            'defaultVersion' => $defaultVersion,
            'defaultTier' => $defaultTier,
            'topRankScore' => $topRankScore,
            'data' => $lastData,
            'versions' => $versions,
        ];
        return view('equipment', $data);
    }

}
