<?php

namespace App\Http\Controllers;

use App\Services\GameResultSummaryService;
use App\Services\MainService;
use App\Services\RankRangeService;
use Illuminate\Http\Request;

class MainController extends Controller
{
    protected MainService $mainService;
    protected RankRangeService $rankRangeService;
    protected int $versionSeason;
    protected int $versionMajor;
    protected int $versionMinor;
    protected string $minTier;

    public function __construct(MainService $mainService, RankRangeService $rankRangeService)
    {
        $this->mainService = $mainService;
        $this->rankRangeService = $rankRangeService;
        $this->mainService->getLatestVersion();
        $this->versionSeason = 0;
        $this->versionMajor = 0;
        $this->versionMinor = 0;
        $this->minTier = 'diamond';
    }
    public function index(Request $request)
    {
        $defaultTier = config('erDev.defaultTier');
        $defaultVersion = config('erDev.defaultVersion');
        $minTier = $request->input('min_tier', $defaultTier);
        $version = $request->input('version', $defaultVersion);
        
        // 버전 형식 검증
        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            $version = $defaultVersion;
        }
        
        $version =  explode('.', $version);
        $versionSeason = $version[0];
        $versionMajor = $version[1];
        $versionMinor = $version[2];
        
        // 추가 검증 - 숫자 범위 확인
        if (!is_numeric($versionSeason) || !is_numeric($versionMajor) || !is_numeric($versionMinor) ||
            $versionSeason < 0 || $versionSeason > 999 ||
            $versionMajor < 0 || $versionMajor > 999 ||
            $versionMinor < 0 || $versionMinor > 999) {
            // 잘못된 버전이면 기본값 사용
            $version =  explode('.', $defaultVersion);
            $versionSeason = $version[0];
            $versionMajor = $version[1];
            $versionMinor = $version[2];
        }
        
        $filters = [
            'version_season' => $versionSeason,
            'version_major' => $versionMajor,
            'version_minor' => $versionMinor,
            'min_tier' => $minTier,
        ];
        
        // 버전별 최상위 티어 점수 동적 조회
        $versionFilters = [
            'version_season' => $versionSeason,
            'version_major' => $versionMajor,
            'version_minor' => $versionMinor
        ];
        $topRankScore = $this->rankRangeService->getTopTierMinScore($versionFilters);
        
        $lastData = $this->mainService->getGameResultSummary($filters);
        if ($lastData->first()) {
            $lastUpdate = $lastData->first()->created_at ?? null;
        } else {
            $lastUpdate = null;
        }

        $versions = $this->mainService->getLatestVersionList();

        $data = [
            'lastUpdate' => $lastUpdate,
            'defaultVersion' => $defaultVersion,
            'defaultTier' => $defaultTier,
            'topRankScore' => $topRankScore,
            'data' => $lastData,
            'versions' => $versions,
        ];
        return view('main', $data);
    }


    public function show(Request $request, $types)
    {
        $defaultTier = config('erDev.defaultTier');
        $defaultVersion = config('erDev.defaultVersion');
        $minTier = $request->input('min_tier', $defaultTier);
        $version = $request->input('version', $defaultVersion);
        $version =  explode('.', $version);

        [$characterName, $weaponType] = array_pad(explode('-', $types), 2, null);
        [$defaultVersionSeason, $defaultVersionMajor, $defaultVersionMinor] =  array_pad(explode('.', $defaultVersion), 3, null);
        $versionSeason = $version[0] ?? $defaultVersionSeason;
        $versionMajor = $version[1] ?? $defaultVersionMajor;
        $versionMinor = $version[2] ?? $defaultVersionMinor;
        $weaponType = empty($weaponType) ? 'All' : $weaponType;
        
        // 버전별 최상위 티어 점수 동적 조회
        $versionFilters = [
            'version_season' => $versionSeason,
            'version_major' => $versionMajor,
            'version_minor' => $versionMinor
        ];
        $topRankScore = $this->rankRangeService->getTopTierMinScore($versionFilters);
        $filters = [
            'version_season' => $versionSeason,
            'version_major' => $versionMajor,
            'version_minor' => $versionMinor,
            'character_name' => $characterName,
            'weapon_type' => $weaponType,
            'min_tier' => $minTier,
        ];

        $versions = $this->mainService->getLatestVersionList();
        $data = [
            'minTier' => $minTier,
            'versionSeason' => $versionSeason,
            'versionMajor' => $versionMajor,
            'versionMinor' => $versionMinor,
            'characterName' => $characterName,
            'weaponType' => $weaponType,
            'defaultVersion' => $defaultVersion,
            'topRankScore' => $topRankScore,
            'defaultTier' => $defaultTier,
            'versions' => $versions,
        ];
        $data['byMain'] = $this->mainService->getGameResultSummaryDetail($filters);
        $byMainFilter = $filters;
        unset($byMainFilter['character_name']);
        unset($byMainFilter['weapon_type']);
        $data['byMainCount'] = $this->mainService->getGameResultSummary($byMainFilter)->count();
        $data['byRank'] = $this->mainService->getGameResultRankSummary($filters);
        $byTacticalSkill = $this->mainService->getGameResultTacticalSkillSummary($filters);
        $data['byTacticalSkillData'] = $byTacticalSkill['data'];
        $data['byTacticalSkillTotal'] = $byTacticalSkill['total'];

        $byEquipment = $this->mainService->getGameResultEquipmentSummary($filters);
        $data['byEquipmentData'] = $byEquipment['data'];
        $data['byEquipmentTotal'] = $byEquipment['total'];

        $byTrait = $this->mainService->getGameResultTraitSummary($filters);
        $data['byTraitData'] = $byTrait['data'];
        $data['byTraitTotal'] = $byTrait['total'];

        // Extract unique trait categories for filtering
        $traitCategories = [];
        foreach ($data['byTraitData'] as $traitGroup) {
            $firstTraitItem = reset($traitGroup); // Get the first item to access category
            if ($firstTraitItem && !in_array($firstTraitItem->trait_category, $traitCategories)) {
                $traitCategories[] = $firstTraitItem->trait_category;
            }
        }
        sort($traitCategories); // Sort categories alphabetically
        $data['traitCategories'] = $traitCategories;


        return view('detail', $data);
    }

    public function test()
    {
        (new GameResultSummaryService())->updateGameResultSummary(null, null);
        return view('welcome');
    }
}
