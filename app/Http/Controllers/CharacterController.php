<?php

namespace App\Http\Controllers;

use App\Services\GameResultSummaryService;
use App\Services\GameResultTraitCombinationSummaryService;
use App\Services\MainService;
use App\Services\PerformanceMonitor;
use App\Services\RankRangeService;
use App\Services\TraitGroupService;
use App\Traits\ErDevTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $defaultTier = default_tier();
        $defaultVersion = default_version();
        $minTier = $request->input('min_tier', $defaultTier);
        $version = $request->input('version', $defaultVersion);

        // 버전 파싱/검증 (핫픽스 포함, 예: 12.2.0b)
        $versionParts = parse_version_key($version, $defaultVersion);
        $versionSeason = $versionParts['version_season'];
        $versionMajor = $versionParts['version_major'];
        $versionMinor = $versionParts['version_minor'];
        $versionHotfix = $versionParts['version_hotfix'];

        // 캐시 키 생성
        $cacheKey = "game_character_{$minTier}_" . $versionParts['cache_key'];
        $cacheDuration = config('erDev.cacheDuration'); // 캐시 지속 시간

        // 캐시에서 데이터 조회
        $data = cache()->get($cacheKey);

        // 캐시가 없거나 데이터가 비어있으면 새로 조회
        if (!$data || empty($data['data']) || (is_countable($data['data']) && count($data['data']) === 0)) {
            $filters = [
                'version_season' => $versionSeason,
                'version_major' => $versionMajor,
                'version_minor' => $versionMinor,
                'version_hotfix' => $versionHotfix,
                'min_tier' => $minTier,
            ];

            // 버전별 최상위 티어 점수 동적 조회
            $versionFilters = [
                'version_season' => $versionSeason,
                'version_major' => $versionMajor,
                'version_minor' => $versionMinor,
                'version_hotfix' => $versionHotfix,
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

        // 태그 데이터는 캐시 외부에서 별도 조회 (즉시 반영)
        $weaponTagMap = $this->getWeaponTagMap();

        $data['weaponTagMap'] = $weaponTagMap;

        return view('character', $data);
    }

    private function getWeaponTagMap(): array
    {
        $rows = DB::table('character_weapon_character_tag as cwct')
            ->join('character_tags as ct', 'ct.id', '=', 'cwct.character_tag_id')
            ->select('cwct.character_id', 'cwct.weapon_type', 'ct.name as tag_name')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $key = $row->character_id . '_' . $row->weapon_type;
            $map[$key][] = $row->tag_name;
        }
        return $map;
    }

    public function show(Request $request, $types)
    {
        $defaultTier = default_tier();
        $defaultVersion = default_version();
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

        // 버전 파싱/검증 (핫픽스 포함, 예: 12.2.0b)
        $versionParts = parse_version_key($version, $defaultVersion);
        $versionSeason = $versionParts['version_season'];
        $versionMajor = $versionParts['version_major'];
        $versionMinor = $versionParts['version_minor'];
        $versionHotfix = $versionParts['version_hotfix'];

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
            'version_minor' => $versionMinor,
            'version_hotfix' => $versionHotfix,
        ];
        $topRankScore = $this->rankRangeService->getTopTierMinScore($versionFilters);

        $filters = [
            'version_season' => $versionSeason,
            'version_major' => $versionMajor,
            'version_minor' => $versionMinor,
            'version_hotfix' => $versionHotfix,
            'character_name' => $characterName,
            'weapon_type' => $weaponType,
            'min_tier' => $minTier,
        ];

        $versions = $this->mainService->getLatestVersionList();

        // 기본 정보만 로드 (레이지 로딩)
        $cacheKey = "game_detail_basic_{$types}_{$minTier}_" . $versionParts['cache_key'];
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

        // 순위 통계도 서버에서 미리 로드 (항상 보이는 핵심 데이터)
        $rankCacheKey = "game_detail_ranks_{$types}_{$minTier}_" . $versionParts['cache_key'];
        $byRank = cache()->get($rankCacheKey);

        if (empty($byRank)) {
            $byRank = $this->mainService->getGameResultRankSummary($filters);

            if (!empty($byRank)) {
                cache()->put($rankCacheKey, $byRank, $cacheDuration);
            }
        }

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
            'byRank' => $byRank, // 순위 통계 서버 렌더링
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
        $defaultTier = default_tier();
        $defaultVersion = default_version();
        $minTier = $request->input('min_tier', $defaultTier);
        $version = $request->input('version', $defaultVersion);

        // 버전 파싱/검증 (핫픽스 포함, 예: 12.2.0b)
        $versionParts = parse_version_key($version, $defaultVersion);
        $versionSeason = $versionParts['version_season'];
        $versionMajor = $versionParts['version_major'];
        $versionMinor = $versionParts['version_minor'];
        $versionHotfix = $versionParts['version_hotfix'];

        // 파라미터 파싱
        [$characterName, $weaponType] = array_pad(explode('-', $types), 2, null);
        $weaponType = empty($weaponType) ? 'All' : $weaponType;

        $filters = [
            'version_season' => $versionSeason,
            'version_major' => $versionMajor,
            'version_minor' => $versionMinor,
            'version_hotfix' => $versionHotfix,
            'character_name' => $characterName,
            'weapon_type' => $weaponType,
            'min_tier' => $minTier,
        ];

        $cacheKey = "game_detail_tiers_{$types}_{$minTier}_" . $versionParts['cache_key'];
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
        $defaultTier = default_tier();
        $defaultVersion = default_version();
        $minTier = $request->input('min_tier', $defaultTier);
        $version = $request->input('version', $defaultVersion);

        // 버전 파싱/검증 (핫픽스 포함, 예: 12.2.0b)
        $versionParts = parse_version_key($version, $defaultVersion);
        $versionSeason = $versionParts['version_season'];
        $versionMajor = $versionParts['version_major'];
        $versionMinor = $versionParts['version_minor'];
        $versionHotfix = $versionParts['version_hotfix'];

        [$characterName, $weaponType] = array_pad(explode('-', $types), 2, null);
        $weaponType = empty($weaponType) ? 'All' : $weaponType;

        $filters = [
            'version_season' => $versionSeason,
            'version_major' => $versionMajor,
            'version_minor' => $versionMinor,
            'version_hotfix' => $versionHotfix,
            'character_name' => $characterName,
            'weapon_type' => $weaponType,
            'min_tier' => $minTier,
        ];

        $cacheKey = "game_detail_ranks_{$types}_{$minTier}_" . $versionParts['cache_key'];
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
        $defaultTier = default_tier();
        $defaultVersion = default_version();
        $minTier = $request->input('min_tier', $defaultTier);
        $version = $request->input('version', $defaultVersion);

        // 버전 파싱/검증 (핫픽스 포함, 예: 12.2.0b)
        $versionParts = parse_version_key($version, $defaultVersion);
        $versionSeason = $versionParts['version_season'];
        $versionMajor = $versionParts['version_major'];
        $versionMinor = $versionParts['version_minor'];
        $versionHotfix = $versionParts['version_hotfix'];

        [$characterName, $weaponType] = array_pad(explode('-', $types), 2, null);
        $weaponType = empty($weaponType) ? 'All' : $weaponType;

        $filters = [
            'version_season' => $versionSeason,
            'version_major' => $versionMajor,
            'version_minor' => $versionMinor,
            'version_hotfix' => $versionHotfix,
            'character_name' => $characterName,
            'weapon_type' => $weaponType,
            'min_tier' => $minTier,
        ];

        $cacheKey = "game_detail_tactical_{$types}_{$minTier}_" . $versionParts['cache_key'];
        $cacheDuration = config('erDev.cacheDuration');

        $data = cache()->get($cacheKey);

        // 캐시가 없거나 데이터가 비어있으면 새로 조회
        if (empty($data) || empty($data['byTacticalSkillData'])) {
            $byTacticalSkill = $this->mainService->getGameResultTacticalSkillSummary($filters);

            $data = [
                'byTacticalSkillData' => $byTacticalSkill['data'],
                'byTacticalSkillTotal' => $byTacticalSkill['total'],
                'aggregatedData' => $byTacticalSkill['aggregatedData'] ?? [],
                'aggregatedBySkill' => $byTacticalSkill['aggregatedBySkill'] ?? [],
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
        $defaultTier = default_tier();
        $defaultVersion = default_version();
        $minTier = $request->input('min_tier', $defaultTier);
        $version = $request->input('version', $defaultVersion);

        // 버전 파싱/검증 (핫픽스 포함, 예: 12.2.0b)
        $versionParts = parse_version_key($version, $defaultVersion);
        $versionSeason = $versionParts['version_season'];
        $versionMajor = $versionParts['version_major'];
        $versionMinor = $versionParts['version_minor'];
        $versionHotfix = $versionParts['version_hotfix'];

        [$characterName, $weaponType] = array_pad(explode('-', $types), 2, null);
        $weaponType = empty($weaponType) ? 'All' : $weaponType;

        $filters = [
            'version_season' => $versionSeason,
            'version_major' => $versionMajor,
            'version_minor' => $versionMinor,
            'version_hotfix' => $versionHotfix,
            'character_name' => $characterName,
            'weapon_type' => $weaponType,
            'min_tier' => $minTier,
        ];

        $cacheKey = "game_detail_equipment_{$types}_{$minTier}_" . $versionParts['cache_key'];
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
        $defaultTier = default_tier();
        $defaultVersion = default_version();
        $minTier = $request->input('min_tier', $defaultTier);
        $version = $request->input('version', $defaultVersion);

        // 버전 파싱/검증 (핫픽스 포함, 예: 12.2.0b)
        $versionParts = parse_version_key($version, $defaultVersion);
        $versionSeason = $versionParts['version_season'];
        $versionMajor = $versionParts['version_major'];
        $versionMinor = $versionParts['version_minor'];
        $versionHotfix = $versionParts['version_hotfix'];

        [$characterName, $weaponType] = array_pad(explode('-', $types), 2, null);
        $weaponType = empty($weaponType) ? 'All' : $weaponType;

        $filters = [
            'version_season' => $versionSeason,
            'version_major' => $versionMajor,
            'version_minor' => $versionMinor,
            'version_hotfix' => $versionHotfix,
            'character_name' => $characterName,
            'weapon_type' => $weaponType,
            'min_tier' => $minTier,
        ];

        $cacheKey = "game_detail_traits_{$types}_{$minTier}_" . $versionParts['cache_key'];
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

    public function getDetailSynergy(Request $request, $types)
    {
        $defaultTier = default_tier();
        $defaultVersion = default_version();
        $minTier = $request->input('min_tier', $defaultTier);
        $version = $request->input('version', $defaultVersion);

        // 버전 파싱/검증 (핫픽스 포함, 예: 12.2.0b)
        $versionParts = parse_version_key($version, $defaultVersion);
        $versionSeason = $versionParts['version_season'];
        $versionMajor = $versionParts['version_major'];
        $versionMinor = $versionParts['version_minor'];
        $versionHotfix = $versionParts['version_hotfix'];

        [$characterName, $weaponType] = array_pad(explode('-', $types), 2, null);
        $weaponType = empty($weaponType) ? 'All' : $weaponType;

        $filters = [
            'version_season' => $versionSeason,
            'version_major' => $versionMajor,
            'version_minor' => $versionMinor,
            'version_hotfix' => $versionHotfix,
            'character_name' => $characterName,
            'weapon_type' => $weaponType,
            'min_tier' => $minTier,
        ];

        $cacheKey = "game_detail_synergy_{$types}_{$minTier}_" . $versionParts['cache_key'];
        $cacheDuration = config('erDev.cacheDuration');

        $data = cache()->get($cacheKey);

        if (empty($data)) {
            $data = $this->mainService->getGameResultSynergySummary($filters);

            if ($data && count($data) > 0) {
                cache()->put($cacheKey, $data, $cacheDuration);
            }
        }

        return response()->json(['data' => $data]);
    }

    public function getDetailTraitCombinations(Request $request, $types)
    {
        $defaultTier = default_tier();
        $defaultVersion = default_version();
        $minTier = $request->input('min_tier', $defaultTier);
        $version = $request->input('version', $defaultVersion);

        // 버전 파싱/검증 (핫픽스 포함, 예: 12.2.0b)
        $versionParts = parse_version_key($version, $defaultVersion);
        $versionSeason = $versionParts['version_season'];
        $versionMajor = $versionParts['version_major'];
        $versionMinor = $versionParts['version_minor'];
        $versionHotfix = $versionParts['version_hotfix'];

        [$characterName, $weaponType] = array_pad(explode('-', $types), 2, null);
        $weaponType = empty($weaponType) ? 'All' : $weaponType;

        $filters = [
            'version_season' => $versionSeason,
            'version_major' => $versionMajor,
            'version_minor' => $versionMinor,
            'version_hotfix' => $versionHotfix,
            'character_name' => $characterName,
            'weapon_type' => $weaponType,
            'min_tier' => $minTier,
        ];

        $cacheKey = "game_detail_trait_combinations_{$types}_{$minTier}_" . $versionParts['cache_key'];
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

            // 특성 그룹은 패치마다 바뀌므로 해당 버전 기준 값으로 덮어쓴다.
            $groupMap = (new TraitGroupService())->getGroupMap([
                'version_season' => $versionSeason,
                'version_major' => $versionMajor,
                'version_minor' => $versionMinor,
                'version_hotfix' => $versionHotfix,
            ]);
            foreach ($traits as $trait) {
                $trait->trait_group = $groupMap[$trait->id] ?? ($trait->is_main ? 'main' : null);
            }

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

    /**
     * 캐릭터 지표 추이 (최근 15일)
     *
     * 스냅샷 테이블만 읽으므로 집계 테이블을 훑지 않는다.
     */
    public function getDetailTrend(Request $request, $types)
    {
        $defaultTier = default_tier();
        $minTier = $request->input('min_tier', $defaultTier);

        [$characterName, $weaponType] = array_pad(explode('-', $types), 2, null);
        $weaponType = empty($weaponType) ? 'All' : $weaponType;

        if (empty($characterName)) {
            return response()->json(['points' => []]);
        }

        $trend = app(\App\Services\SummaryTrendService::class)
            ->getTrend($characterName, $weaponType, $minTier);

        return response()->json($trend);
    }

    public function getDetailPatchHistory(Request $request, $types)
    {
        [$characterName] = array_pad(explode('-', $types), 2, null);

        if (empty($characterName)) {
            return response()->json(['data' => []]);
        }

        $character = \App\Models\Character::where('name', $characterName)->first();

        if (!$character) {
            return response()->json(['data' => []]);
        }

        $patchNotes = \App\Models\PatchNote::with('versionHistory')
            ->where('category', '캐릭터')
            ->where('target_id', $character->id)
            ->orderBy('version_history_id', 'desc')
            ->get();

        if ($patchNotes->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $grouped = $patchNotes->groupBy('version_history_id');

        $data = [];
        foreach ($grouped as $notes) {
            $versionHistory = $notes->first()->versionHistory;
            if (!$versionHistory) continue;

            $data[] = [
                'version' => $versionHistory->version,
                'start_date' => $versionHistory->start_date?->format('Y-m-d'),
                'patch_notes' => $notes->map(fn($note) => [
                    'patch_type' => $note->patch_type,
                    'content' => $note->content,
                    'weapon_type' => $note->weapon_type,
                    'weapon_type_en' => $note->weapon_type ? $this->replaceWeaponType($note->weapon_type, 'en') : null,
                    'skill_type' => $note->skill_type,
                ])->values(),
            ];
        }

        return response()->json(['data' => $data]);
    }
}
