<?php

namespace App\Http\Controllers;

use App\Services\GameResultSummaryService;
use App\Services\GameResultTraitCombinationSummaryService;
use App\Services\MainService;
use App\Services\PerformanceMonitor;
use App\Services\RankRangeService;
use App\Traits\ErDevTrait;
use Illuminate\Http\Request;

class CharacterController extends Controller
{
    use ErDevTrait;
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

        // 캐시 키 생성
        $cacheKey = "game_character_{$minTier}_" . implode('_', $version);
        $cacheDuration = config('erDev.cacheDuration'); // 캐시 지속 시간

        // 캐시에서 데이터 조회
        $data = cache()->get($cacheKey);

        // 캐시가 없거나 데이터가 비어있으면 새로 조회
        if (!$data || empty($data['data']) || (is_countable($data['data']) && count($data['data']) === 0)) {
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

            // 데이터가 있을 때만 캐싱
            if ($lastData && count($lastData) > 0) {
                cache()->put($cacheKey, $data, $cacheDuration);
            }
        }

        return view('character', $data);
    }


    public function show(Request $request, $types)
    {
        $defaultTier = config('erDev.defaultTier');
        $defaultVersion = config('erDev.defaultVersion');
        $minTier = $request->input('min_tier', $defaultTier);
        $version = $request->input('version', $defaultVersion);

        // types 파라미터 검증
        if (empty($types) || !is_string($types)) {
            return view('detail-not-found', [
                'message' => '잘못된 캐릭터 파라미터입니다.',
                'defaultVersion' => $defaultVersion,
                'defaultTier' => $defaultTier,
            ]);
        }

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

        // 파라미터 파싱
        [$characterName, $weaponType] = array_pad(explode('-', $types), 2, null);

        // characterName 검증
        if (empty($characterName)) {
            return view('detail-not-found', [
                'message' => '잘못된 캐릭터 파라미터입니다.',
                'defaultVersion' => $defaultVersion,
                'defaultTier' => $defaultTier,
            ]);
        }

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

        // 기본 정보만 로드 (레이지 로딩)
        $cacheKey = "game_detail_basic_{$types}_{$minTier}_" . implode('_', $version);
        $cacheDuration = config('erDev.cacheDuration');

        $byMain = cache()->get($cacheKey);

        // 캐시가 없거나 데이터가 비어있으면 새로 조회
        if (empty($byMain)) {
            $byAll = $this->mainService->getGameResultSummaryDetailBulk($filters, $this->tierRange);
            $byMain = $byAll[$minTier] ?? null;

            // 데이터가 있을 때만 캐싱
            if (!empty($byMain)) {
                cache()->put($cacheKey, $byMain, $cacheDuration);
            }
        }

        // 데이터가 없는 경우 처리
        if (empty($byMain)) {
            return view('detail-not-found', [
                'message' => '해당 캐릭터의 데이터를 찾을 수 없습니다.',
                'characterName' => $types,
                'defaultVersion' => $defaultVersion,
                'defaultTier' => $defaultTier,
            ]);
        }

        // rank_count를 byMain에서 직접 가져오기 (중복 쿼리 제거)
        $byMainCount = $byMain->rank_count ?? 0;

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
            'byMain' => $byMain,
            'byMainCount' => $byMainCount,
            // 나머지 데이터는 AJAX로 로드
        ];

        return view('detail', $data);
    }

    public function test()
    {
        (new GameResultSummaryService())->updateGameResultSummary(null, null);
        return view('welcome');
    }

    // Lazy Loading API Endpoints
    public function getDetailTiers(Request $request, $types)
    {
        $defaultTier = config('erDev.defaultTier');
        $defaultVersion = config('erDev.defaultVersion');
        $minTier = $request->input('min_tier', $defaultTier);
        $version = $request->input('version', $defaultVersion);

        // 버전 검증
        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            $version = $defaultVersion;
        }

        $version = explode('.', $version);
        $versionSeason = $version[0];
        $versionMajor = $version[1];
        $versionMinor = $version[2];

        // 파라미터 파싱
        [$characterName, $weaponType] = array_pad(explode('-', $types), 2, null);
        $weaponType = empty($weaponType) ? 'All' : $weaponType;

        $filters = [
            'version_season' => $versionSeason,
            'version_major' => $versionMajor,
            'version_minor' => $versionMinor,
            'character_name' => $characterName,
            'weapon_type' => $weaponType,
            'min_tier' => $minTier,
        ];

        $cacheKey = "game_detail_tiers_{$types}_{$minTier}_" . implode('_', $version);
        $cacheDuration = config('erDev.cacheDuration');

        $byAll = cache()->get($cacheKey);

        // 캐시가 없거나 데이터가 비어있으면 새로 조회
        if (empty($byAll)) {
            $byAll = $this->mainService->getGameResultSummaryDetailBulk($filters, $this->tierRange);

            // 데이터가 있을 때만 캐싱
            if (!empty($byAll)) {
                cache()->put($cacheKey, $byAll, $cacheDuration);
            }
        }

        return response()->json(['byAll' => $byAll]);
    }

    public function getDetailRanks(Request $request, $types)
    {
        $defaultTier = config('erDev.defaultTier');
        $defaultVersion = config('erDev.defaultVersion');
        $minTier = $request->input('min_tier', $defaultTier);
        $version = $request->input('version', $defaultVersion);

        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            $version = $defaultVersion;
        }

        $version = explode('.', $version);
        $versionSeason = $version[0];
        $versionMajor = $version[1];
        $versionMinor = $version[2];

        [$characterName, $weaponType] = array_pad(explode('-', $types), 2, null);
        $weaponType = empty($weaponType) ? 'All' : $weaponType;

        $filters = [
            'version_season' => $versionSeason,
            'version_major' => $versionMajor,
            'version_minor' => $versionMinor,
            'character_name' => $characterName,
            'weapon_type' => $weaponType,
            'min_tier' => $minTier,
        ];

        $cacheKey = "game_detail_ranks_{$types}_{$minTier}_" . implode('_', $version);
        $cacheDuration = config('erDev.cacheDuration');

        $byRank = cache()->get($cacheKey);

        // 캐시가 없거나 데이터가 비어있으면 새로 조회
        if (empty($byRank)) {
            $byRank = $this->mainService->getGameResultRankSummary($filters);

            // 데이터가 있을 때만 캐싱
            if (!empty($byRank)) {
                cache()->put($cacheKey, $byRank, $cacheDuration);
            }
        }

        return response()->json(['byRank' => $byRank]);
    }

    public function getDetailTacticalSkills(Request $request, $types)
    {
        $defaultTier = config('erDev.defaultTier');
        $defaultVersion = config('erDev.defaultVersion');
        $minTier = $request->input('min_tier', $defaultTier);
        $version = $request->input('version', $defaultVersion);

        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            $version = $defaultVersion;
        }

        $version = explode('.', $version);
        $versionSeason = $version[0];
        $versionMajor = $version[1];
        $versionMinor = $version[2];

        [$characterName, $weaponType] = array_pad(explode('-', $types), 2, null);
        $weaponType = empty($weaponType) ? 'All' : $weaponType;

        $filters = [
            'version_season' => $versionSeason,
            'version_major' => $versionMajor,
            'version_minor' => $versionMinor,
            'character_name' => $characterName,
            'weapon_type' => $weaponType,
            'min_tier' => $minTier,
        ];

        $cacheKey = "game_detail_tactical_{$types}_{$minTier}_" . implode('_', $version);
        $cacheDuration = config('erDev.cacheDuration');

        $data = cache()->get($cacheKey);

        // 캐시가 없거나 데이터가 비어있으면 새로 조회
        if (empty($data) || empty($data['byTacticalSkillData'])) {
            $byTacticalSkill = $this->mainService->getGameResultTacticalSkillSummary($filters);

            $data = [
                'byTacticalSkillData' => $byTacticalSkill['data'],
                'byTacticalSkillTotal' => $byTacticalSkill['total'],
                'aggregatedData' => $byTacticalSkill['aggregatedData'] ?? [],
            ];

            // 데이터가 있을 때만 캐싱
            if (!empty($data['byTacticalSkillData'])) {
                cache()->put($cacheKey, $data, $cacheDuration);
            }
        }

        return response()->json($data);
    }

    public function getDetailEquipment(Request $request, $types)
    {
        $defaultTier = config('erDev.defaultTier');
        $defaultVersion = config('erDev.defaultVersion');
        $minTier = $request->input('min_tier', $defaultTier);
        $version = $request->input('version', $defaultVersion);

        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            $version = $defaultVersion;
        }

        $version = explode('.', $version);
        $versionSeason = $version[0];
        $versionMajor = $version[1];
        $versionMinor = $version[2];

        [$characterName, $weaponType] = array_pad(explode('-', $types), 2, null);
        $weaponType = empty($weaponType) ? 'All' : $weaponType;

        $filters = [
            'version_season' => $versionSeason,
            'version_major' => $versionMajor,
            'version_minor' => $versionMinor,
            'character_name' => $characterName,
            'weapon_type' => $weaponType,
            'min_tier' => $minTier,
        ];

        $cacheKey = "game_detail_equipment_{$types}_{$minTier}_" . implode('_', $version);
        $cacheDuration = config('erDev.cacheDuration');

        $data = cache()->get($cacheKey);

        // 캐시가 없거나 데이터가 비어있으면 새로 조회
        if (empty($data) || empty($data['byEquipmentData'])) {
            $byEquipment = $this->mainService->getGameResultEquipmentSummary($filters);

            $data = [
                'byEquipmentData' => $byEquipment['data'],
                'byEquipmentTotal' => $byEquipment['total'],
                'aggregatedData' => $byEquipment['aggregatedData'] ?? [],
            ];

            // 데이터가 있을 때만 캐싱
            if (!empty($data['byEquipmentData'])) {
                cache()->put($cacheKey, $data, $cacheDuration);
            }
        }

        return response()->json($data);
    }

    public function getDetailTraits(Request $request, $types)
    {
        $defaultTier = config('erDev.defaultTier');
        $defaultVersion = config('erDev.defaultVersion');
        $minTier = $request->input('min_tier', $defaultTier);
        $version = $request->input('version', $defaultVersion);

        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            $version = $defaultVersion;
        }

        $version = explode('.', $version);
        $versionSeason = $version[0];
        $versionMajor = $version[1];
        $versionMinor = $version[2];

        [$characterName, $weaponType] = array_pad(explode('-', $types), 2, null);
        $weaponType = empty($weaponType) ? 'All' : $weaponType;

        $filters = [
            'version_season' => $versionSeason,
            'version_major' => $versionMajor,
            'version_minor' => $versionMinor,
            'character_name' => $characterName,
            'weapon_type' => $weaponType,
            'min_tier' => $minTier,
        ];

        $cacheKey = "game_detail_traits_{$types}_{$minTier}_" . implode('_', $version);
        $cacheDuration = config('erDev.cacheDuration');

        $data = cache()->get($cacheKey);

        // 캐시가 없거나 데이터가 비어있으면 새로 조회
        if (empty($data) || empty($data['byTraitData'])) {
            $byTrait = $this->mainService->getGameResultTraitSummary($filters);

            // Extract unique trait categories for filtering
            $traitCategories = [];
            foreach ($byTrait['data'] as $traitGroup) {
                $firstTraitItem = reset($traitGroup);
                if ($firstTraitItem && !in_array($firstTraitItem->trait_category, $traitCategories)) {
                    $traitCategories[] = $firstTraitItem->trait_category;
                }
            }
            sort($traitCategories);

            $data = [
                'byTraitData' => $byTrait['data'],
                'byTraitTotal' => $byTrait['total'],
                'traitCategories' => $traitCategories,
                'aggregatedData' => $byTrait['aggregatedData'] ?? [],
            ];

            // 데이터가 있을 때만 캐싱
            if (!empty($data['byTraitData'])) {
                cache()->put($cacheKey, $data, $cacheDuration);
            }
        }

        return response()->json($data);
    }

    public function getDetailTraitCombinations(Request $request, $types)
    {
        $defaultTier = config('erDev.defaultTier');
        $defaultVersion = config('erDev.defaultVersion');
        $minTier = $request->input('min_tier', $defaultTier);
        $version = $request->input('version', $defaultVersion);

        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            $version = $defaultVersion;
        }

        $version = explode('.', $version);
        $versionSeason = $version[0];
        $versionMajor = $version[1];
        $versionMinor = $version[2];

        [$characterName, $weaponType] = array_pad(explode('-', $types), 2, null);
        $weaponType = empty($weaponType) ? 'All' : $weaponType;

        $filters = [
            'version_season' => $versionSeason,
            'version_major' => $versionMajor,
            'version_minor' => $versionMinor,
            'character_name' => $characterName,
            'weapon_type' => $weaponType,
            'min_tier' => $minTier,
        ];

        $cacheKey = "game_detail_trait_combinations_{$types}_{$minTier}_" . implode('_', $version);
        $cacheDuration = config('erDev.cacheDuration');

        $data = cache()->get($cacheKey);

        // 캐시가 없거나 데이터가 비어있으면 새로 조회
        if (empty($data) || empty($data['data'])) {
            $service = new GameResultTraitCombinationSummaryService();
            $result = $service->getDetail($filters);

            // 특성 정보 조회 (아이콘용)
            $traitIds = [];
            foreach ($result['data'] as $item) {
                $ids = explode(',', $item->trait_ids);
                foreach ($ids as $id) {
                    if (!in_array($id, $traitIds)) {
                        $traitIds[] = $id;
                    }
                }
            }

            // 특성 정보 가져오기
            $traits = \App\Models\GameTrait::whereIn('id', $traitIds)->get()->keyBy('id');

            $data = [
                'data' => $result['data'],
                'total' => $result['total'],
                'traits' => $traits,
            ];

            // 데이터가 있을 때만 캐싱
            if (!empty($data['data']) && count($data['data']) > 0) {
                cache()->put($cacheKey, $data, $cacheDuration);
            }
        }

        return response()->json($data);
    }
}
