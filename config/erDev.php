<?php
return [
    'apiKey' => env('ER_API_KEY'),
    'fetchGameUnitNumber' => env('ER_FETCH_GAME_UNIT_NUMBER', 30),
    'searchGameNumber' => env('ER_SEARCH_GAME_NUMBER', 5),
    'defaultTier' => env('ER_STAT_DEFALT_TIER', 'Platinum'),
    'defaultVersion' => env('ER_STAT_DEFALT_VERSION', '42.0'),
    'topRankScore' => env('ER_STAT_TOP_RANK_SCORE', '8000'),
    'imageVersion' => env('IMAGE_VERSION', 'v2'), // 이미지 캐시 버스팅용 버전
    'cacheDuration' => env('CACHE_DURATION', 30 * 60), // 캐시 지속 시간 (초 단위, 기본값: 30분)
];
