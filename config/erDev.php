<?php
return [
    'apiKey' => env('ER_API_KEY'),
    'fetchGameUnitNumber' => env('ER_FETCH_GAME_UNIT_NUMBER', 30),
    'searchGameNumber' => env('ER_SEARCH_GAME_NUMBER', 5),
    'defaultTier' => env('ER_STAT_DEFALT_TIER', 'Platinum'),
    'mainPageTier' => env('ER_STAT_MAIN_PAGE_TIER', 'Diamond'),
    'defaultVersion' => env('ER_STAT_DEFALT_VERSION', '42.0'),
    'topRankScore' => env('ER_STAT_TOP_RANK_SCORE', '8000'),
    'imageVersion' => env('IMAGE_VERSION', 'v2'), // 이미지 캐시 버스팅용 버전
    'cacheDuration' => env('CACHE_DURATION', 30 * 60), // 캐시 지속 시간 (초 단위, 기본값: 30분)

    // 특정 캐릭터의 무기 타입 분류 매핑 (character_id => weapon_type_mapping)
    'characterWeaponTypeMapping' => [
        44 => [ // character_id = 44인 캐릭터의 무기 분류
            'DeathAdder' => [131301, 131401, 131502, 601502, 131501, 601501, 131503, 601503],
            'BlackMamba' => [131402, 131505, 601505, 131504, 601504, 131506, 601506],
            'SideWinder' => [131303, 131403, 131508, 601508, 131507, 601507, 131509, 601509],
        ],
    ],
];
