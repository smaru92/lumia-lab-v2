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

if (!function_exists('parse_version_key')) {
    /**
     * 버전 키 문자열을 파싱한다. (예: "12.2.0", "12.2.0b")
     *
     * 핫픽스가 붙은 버전은 별도 통계로 분리되므로 키 자체에 알파벳이 포함된다.
     * 형식이 잘못됐거나 범위를 벗어나면 $fallback(기본: 사이트 기본 버전)을 사용한다.
     *
     * @param string|null $version
     * @param string|null $fallback
     * @return array{version_season:int,version_major:int,version_minor:int,version_hotfix:?string,key:string,cache_key:string}
     */
    function parse_version_key($version, $fallback = null)
    {
        $parsed = _parse_version_key_raw($version);

        if ($parsed === null) {
            $parsed = _parse_version_key_raw($fallback ?? default_version());
        }

        // 폴백까지 잘못된 경우를 대비한 최후 방어
        if ($parsed === null) {
            $parsed = ['version_season' => 0, 'version_major' => 0, 'version_minor' => 0, 'version_hotfix' => null];
        }

        $hotfix = $parsed['version_hotfix'] ?? '';
        $parsed['key'] = "{$parsed['version_season']}.{$parsed['version_major']}.{$parsed['version_minor']}{$hotfix}";
        $parsed['cache_key'] = "{$parsed['version_season']}_{$parsed['version_major']}_{$parsed['version_minor']}{$hotfix}";

        return $parsed;
    }
}

if (!function_exists('_parse_version_key_raw')) {
    /**
     * parse_version_key 내부용 - 형식/범위 검증 후 실패하면 null
     *
     * @param string|null $version
     * @return array|null
     */
    function _parse_version_key_raw($version)
    {
        if (!is_string($version) || !preg_match('/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})([a-z]{1,2})?$/i', $version, $m)) {
            return null;
        }

        return [
            'version_season' => (int) $m[1],
            'version_major' => (int) $m[2],
            'version_minor' => (int) $m[3],
            'version_hotfix' => isset($m[4]) && $m[4] !== '' ? strtolower($m[4]) : null,
        ];
    }
}

if (!function_exists('version_label')) {
    /**
     * 버전 표기 헬퍼
     * 버전 키에 이미 핫픽스가 포함되므로 표기는 키와 동일하다.
     *
     * @param string|\App\Models\VersionHistory|null $version
     * @return string
     */
    function version_label($version)
    {
        if (!$version) {
            return '';
        }

        if ($version instanceof \App\Models\VersionHistory) {
            return $version->version_key;
        }

        return (string) $version;
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


if (!function_exists('site_setting')) {
    /**
     * 사이트 운영 설정 서비스 인스턴스
     *
     * @return \App\Services\SettingService
     */
    function site_setting()
    {
        return app(\App\Services\SettingService::class);
    }
}

if (!function_exists('default_version')) {
    /**
     * 사이트 기본 버전 (관리자 설정, 자동 모드면 등장 후 N시간 지난 최신 버전)
     *
     * @return string
     */
    function default_version()
    {
        return site_setting()->defaultVersion();
    }
}

if (!function_exists('default_tier')) {
    /**
     * 캐릭터/상세 페이지 기본 티어
     *
     * @return string
     */
    function default_tier()
    {
        return site_setting()->defaultTier();
    }
}

if (!function_exists('main_page_tier')) {
    /**
     * 메인 페이지 기준 티어
     *
     * @return string
     */
    function main_page_tier()
    {
        return site_setting()->mainPageTier();
    }
}
