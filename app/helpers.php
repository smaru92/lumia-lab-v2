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

if (!function_exists('version_label')) {
    /**
     * 버전 표기 헬퍼
     * 집계/URL 에 쓰이는 버전 키("12.1.1")를 화면 표기용("12.1.1a")으로 변환한다.
     * 핫픽스 알파벳은 관리자에서 수기로 등록하며, 등록이 없으면 원본 키를 그대로 반환한다.
     *
     * @param string|\App\Models\VersionHistory|null $version 버전 키 문자열 또는 VersionHistory 모델
     * @return string
     */
    function version_label($version)
    {
        if (!$version) {
            return '';
        }

        if ($version instanceof \App\Models\VersionHistory) {
            $version = $version->version_key;
        }

        return \App\Models\VersionHistory::displayVersion((string) $version);
    }
}


if (!function_exists('trait_group_label')) {
    /**
     * 특성 그룹(main/sub1/sub2)을 한글 라벨로 변환
     *
     * @param string|null $group
     * @return string
     */
    function trait_group_label($group)
    {
        return \App\Services\TraitGroupService::groupLabel($group);
    }
}
