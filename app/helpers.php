<?php

if (!function_exists('image_asset')) {
    /**
     * 이미지 캐시 버스팅을 위한 헬퍼 함수
     * 이미지 URL에 버전 쿼리 파라미터를 추가합니다.
     *
     * @param string $path
     * @return string
     */
    function image_asset($path)
    {
        $version = config('erDev.imageVersion', 'v1');
        return asset($path) . '?v=' . $version;
    }
}