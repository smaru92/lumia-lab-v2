@extends('layouts.app')

@section('content')
<div class="container">
    <h2><a href="/">패치 노트 영향 분석</a></h2>

    @if($latestVersion && $previousVersion)
    <div class="version-info-box">
        <h3>버전 비교</h3>
        <p>
            <strong>최신 버전:</strong> {{ $latestVersion->version_season }}.{{ $latestVersion->version_major }}.{{ $latestVersion->version_minor }}
            ({{ $latestVersion->start_date->format('Y-m-d') }})
        </p>
        <p>
            <strong>비교 버전:</strong> {{ $previousVersion->version_season }}.{{ $previousVersion->version_major }}.{{ $previousVersion->version_minor }}
            ({{ $previousVersion->start_date->format('Y-m-d') }})
        </p>
        <p class="version-info-note">
            <small>* 다이아몬드 티어 기준 통계입니다.</small>
        </p>
    </div>

    <!-- 버프된 캐릭터 섹션 -->
    <div class="section-container">
        <h3 class="section-title buffed">
            🔼 버프된 캐릭터 ({{ $buffedCharacters->count() }}개)
        </h3>

        @if($buffedCharacters->count() > 0)
        <table id="buffedTable" class="patch-table buffed">
            <thead>
                <tr>
                    <th class="text-left">캐릭터</th>
                    <th class="text-center">티어 변동</th>
                    <th class="text-center">픽률</th>
                    <th class="text-center">평균 획득점수</th>
                    <th class="text-center">승률</th>
                    <th class="hide-on-mobile text-center">TOP2</th>
                    <th class="hide-on-mobile text-center">TOP4</th>
                    <th class="hide-on-mobile hide-on-tablet text-center">막금구승률</th>
                    <th class="hide-on-mobile text-center">평균 TK</th>
                </tr>
            </thead>
            <tbody>
                @foreach($buffedCharacters as $item)
                <tr data-href="/detail/{{ $item['character_name'] }}-{{ $item['weapon_type_en'] ?? $item['weapon_type'] }}?min_tier=Diamond&version={{ $latestVersion->version_season }}.{{ $latestVersion->version_major }}.{{ $latestVersion->version_minor }}">
                    <td>
                        <div class="character-cell-content">
                            @php
                                $formattedCharacterId = str_pad($item['character_id'], 3, '0', STR_PAD_LEFT);
                                $characterIconPath = image_asset('storage/Character/icon/' . $formattedCharacterId . '.png');
                                $defaultCharacterIconPath = image_asset('storage/Character/icon/default.png');
                                $weaponType = $item['weapon_type'] ?? 'All';
                                $weaponTypeEn = $item['weapon_type_en'] ?? $weaponType;
                                $weaponIconPath = image_asset('storage/Weapon/' . $weaponTypeEn . '.png');
                                $defaultWeaponIconPath = image_asset('storage/Weapon/icon/default.png');
                            @endphp
                            <div class="icon-container">
                                <img src="{{ $characterIconPath }}"
                                     alt="{{ $item['character_name'] }}"
                                     class="character-icon"
                                     loading="lazy"
                                     onerror="this.onerror=null; this.src='{{ $defaultCharacterIconPath }}';">
                                @if($weaponType !== 'All')
                                <img src="{{ $weaponIconPath }}"
                                     alt="{{ $weaponType }}"
                                     class="weapon-icon"
                                     loading="lazy"
                                     onerror="this.onerror=null; this.src='{{ $defaultWeaponIconPath }}';">
                                @endif
                            </div>
                            <div class="character-name-weapon">
                                {{ $item['character_name'] }}<br>
                                @if($weaponType && $weaponType !== 'All')
                                <small>{{ $weaponType }}</small>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="text-center">
                        @php
                            $prevTier = $item['previous']->meta_tier;
                            $latestTier = $item['latest']->meta_tier;
                            $prevTierClass = 'tier-' . strtolower(str_replace(' ', '-', $prevTier));
                            $latestTierClass = 'tier-' . strtolower(str_replace(' ', '-', $latestTier));
                        @endphp
                        <div class="tier-change-container">
                            <span class="tier-badge tier-badge-small {{ $prevTierClass }}">{{ $prevTier }}</span>
                            <span class="tier-arrow">→</span>
                            <span class="tier-badge tier-badge-small {{ $latestTierClass }}">{{ $latestTier }}</span>
                        </div>
                        <div class="meta-score-detail">
                            {{ number_format($item['previous']->meta_score, 2) }} → {{ number_format($item['latest']->meta_score, 2) }}
                        </div>
                        <div class="meta-score-diff {{ $item['meta_score_diff'] > 0 ? 'positive' : ($item['meta_score_diff'] < 0 ? 'negative' : 'neutral') }}">
                            {{ $item['meta_score_diff'] > 0 ? '+' : '' }}{{ number_format($item['meta_score_diff'], 2) }}
                        </div>
                    </td>
                    <td class="text-center">
                        <div>
                            <span class="stat-diff {{ $item['pick_rate_diff'] > 0 ? 'positive' : ($item['pick_rate_diff'] < 0 ? 'negative' : 'neutral') }}">
                                {{ $item['pick_rate_diff'] > 0 ? '+' : '' }}{{ number_format($item['pick_rate_diff'], 2) }}%
                            </span>
                        </div>
                        <div class="stat-detail">
                            {{ number_format($item['previous']->game_count_percent, 2) }}% → {{ number_format($item['latest']->game_count_percent, 2) }}%
                        </div>
                    </td>
                    <td class="text-center">
                        <div>
                            <span class="stat-diff {{ $item['avg_mmr_gain_diff'] > 0 ? 'positive' : ($item['avg_mmr_gain_diff'] < 0 ? 'negative' : 'neutral') }}">
                                {{ $item['avg_mmr_gain_diff'] > 0 ? '+' : '' }}{{ number_format($item['avg_mmr_gain_diff'], 1) }}
                            </span>
                        </div>
                        <div class="stat-detail">
                            {{ number_format($item['previous']->avg_mmr_gain, 1) }} → {{ number_format($item['latest']->avg_mmr_gain, 1) }}
                        </div>
                    </td>
                    <td class="text-center">
                        <div>
                            <span class="stat-diff {{ $item['win_rate_diff'] > 0 ? 'positive' : ($item['win_rate_diff'] < 0 ? 'negative' : 'neutral') }}">
                                {{ $item['win_rate_diff'] > 0 ? '+' : '' }}{{ number_format($item['win_rate_diff'], 2) }}%
                            </span>
                        </div>
                        <div class="stat-detail">
                            {{ number_format($item['previous']->top1_count_percent, 2) }}% → {{ number_format($item['latest']->top1_count_percent, 2) }}%
                        </div>
                    </td>
                    <td class="hide-on-mobile text-center">
                        @php
                            $top2_diff = $item['latest']->top2_count_percent - $item['previous']->top2_count_percent;
                        @endphp
                        <div>
                            <span class="stat-diff {{ $top2_diff > 0 ? 'positive' : ($top2_diff < 0 ? 'negative' : 'neutral') }}">
                                {{ $top2_diff > 0 ? '+' : '' }}{{ number_format($top2_diff, 2) }}%
                            </span>
                        </div>
                        <div class="stat-detail">
                            {{ number_format($item['previous']->top2_count_percent, 2) }}% → {{ number_format($item['latest']->top2_count_percent, 2) }}%
                        </div>
                    </td>
                    <td class="hide-on-mobile text-center">
                        <div>
                            <span class="stat-diff {{ $item['top4_rate_diff'] > 0 ? 'positive' : ($item['top4_rate_diff'] < 0 ? 'negative' : 'neutral') }}">
                                {{ $item['top4_rate_diff'] > 0 ? '+' : '' }}{{ number_format($item['top4_rate_diff'], 2) }}%
                            </span>
                        </div>
                        <div class="stat-detail">
                            {{ number_format($item['previous']->top4_count_percent, 2) }}% → {{ number_format($item['latest']->top4_count_percent, 2) }}%
                        </div>
                    </td>
                    <td class="hide-on-mobile hide-on-tablet text-center">
                        @php
                            $endgame_diff = $item['latest']->endgame_win_percent - $item['previous']->endgame_win_percent;
                        @endphp
                        <div>
                            <span class="stat-diff {{ $endgame_diff > 0 ? 'positive' : ($endgame_diff < 0 ? 'negative' : 'neutral') }}">
                                {{ $endgame_diff > 0 ? '+' : '' }}{{ number_format($endgame_diff, 2) }}%
                            </span>
                        </div>
                        <div class="stat-detail">
                            {{ number_format($item['previous']->endgame_win_percent, 2) }}% → {{ number_format($item['latest']->endgame_win_percent, 2) }}%
                        </div>
                    </td>
                    <td class="hide-on-mobile text-center">
                        @php
                            $tk_diff = $item['latest']->avg_team_kill_score - $item['previous']->avg_team_kill_score;
                        @endphp
                        <div>
                            <span class="stat-diff {{ $tk_diff > 0 ? 'positive' : ($tk_diff < 0 ? 'negative' : 'neutral') }}">
                                {{ $tk_diff > 0 ? '+' : '' }}{{ number_format($tk_diff, 2) }}
                            </span>
                        </div>
                        <div class="stat-detail">
                            {{ number_format($item['previous']->avg_team_kill_score, 2) }} → {{ number_format($item['latest']->avg_team_kill_score, 2) }}
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="empty-message">
            버프된 캐릭터가 없습니다.
        </p>
        @endif
    </div>

    <!-- 너프된 캐릭터 섹션 -->
    <div class="section-container">
        <h3 class="section-title nerfed">
            🔽 너프된 캐릭터 ({{ $nerfedCharacters->count() }}개)
        </h3>

        @if($nerfedCharacters->count() > 0)
        <table id="nerfedTable" class="patch-table nerfed">
            <thead>
                <tr>
                    <th class="text-left">캐릭터</th>
                    <th class="text-center">티어 변동</th>
                    <th class="text-center">픽률</th>
                    <th class="text-center">평균 획득점수</th>
                    <th class="text-center">승률</th>
                    <th class="hide-on-mobile text-center">TOP2</th>
                    <th class="hide-on-mobile text-center">TOP4</th>
                    <th class="hide-on-mobile hide-on-tablet text-center">막금구승률</th>
                    <th class="hide-on-mobile text-center">평균 TK</th>
                </tr>
            </thead>
            <tbody>
                @foreach($nerfedCharacters as $item)
                <tr data-href="/detail/{{ $item['character_name'] }}-{{ $item['weapon_type_en'] ?? $item['weapon_type'] }}?min_tier=Diamond&version={{ $latestVersion->version_season }}.{{ $latestVersion->version_major }}.{{ $latestVersion->version_minor }}">
                    <td>
                        <div class="character-cell-content">
                            @php
                                $formattedCharacterId = str_pad($item['character_id'], 3, '0', STR_PAD_LEFT);
                                $characterIconPath = image_asset('storage/Character/icon/' . $formattedCharacterId . '.png');
                                $defaultCharacterIconPath = image_asset('storage/Character/icon/default.png');
                                $weaponType = $item['weapon_type'] ?? 'All';
                                $weaponTypeEn = $item['weapon_type_en'] ?? $weaponType;
                                $weaponIconPath = image_asset('storage/Weapon/' . $weaponTypeEn . '.png');
                                $defaultWeaponIconPath = image_asset('storage/Weapon/icon/default.png');
                            @endphp
                            <div class="icon-container">
                                <img src="{{ $characterIconPath }}"
                                     alt="{{ $item['character_name'] }}"
                                     class="character-icon"
                                     loading="lazy"
                                     onerror="this.onerror=null; this.src='{{ $defaultCharacterIconPath }}';">
                                @if($weaponType !== 'All')
                                <img src="{{ $weaponIconPath }}"
                                     alt="{{ $weaponType }}"
                                     class="weapon-icon"
                                     loading="lazy"
                                     onerror="this.onerror=null; this.src='{{ $defaultWeaponIconPath }}';">
                                @endif
                            </div>
                            <div class="character-name-weapon">
                                {{ $item['character_name'] }}<br>
                                @if($weaponType && $weaponType !== 'All')
                                <small>{{ $weaponType }}</small>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="text-center">
                        @php
                            $prevTier = $item['previous']->meta_tier;
                            $latestTier = $item['latest']->meta_tier;
                            $prevTierClass = 'tier-' . strtolower(str_replace(' ', '-', $prevTier));
                            $latestTierClass = 'tier-' . strtolower(str_replace(' ', '-', $latestTier));
                        @endphp
                        <div class="tier-change-container">
                            <span class="tier-badge tier-badge-small {{ $prevTierClass }}">{{ $prevTier }}</span>
                            <span class="tier-arrow nerfed">→</span>
                            <span class="tier-badge tier-badge-small {{ $latestTierClass }}">{{ $latestTier }}</span>
                        </div>
                        <div class="meta-score-detail">
                            {{ number_format($item['previous']->meta_score, 2) }} → {{ number_format($item['latest']->meta_score, 2) }}
                        </div>
                        <div class="meta-score-diff {{ $item['meta_score_diff'] > 0 ? 'positive' : ($item['meta_score_diff'] < 0 ? 'negative' : 'neutral') }}">
                            {{ $item['meta_score_diff'] > 0 ? '+' : '' }}{{ number_format($item['meta_score_diff'], 2) }}
                        </div>
                    </td>
                    <td class="text-center">
                        <div>
                            <span class="stat-diff {{ $item['pick_rate_diff'] > 0 ? 'positive' : ($item['pick_rate_diff'] < 0 ? 'negative' : 'neutral') }}">
                                {{ $item['pick_rate_diff'] > 0 ? '+' : '' }}{{ number_format($item['pick_rate_diff'], 2) }}%
                            </span>
                        </div>
                        <div class="stat-detail">
                            {{ number_format($item['previous']->game_count_percent, 2) }}% → {{ number_format($item['latest']->game_count_percent, 2) }}%
                        </div>
                    </td>
                    <td class="text-center">
                        <div>
                            <span class="stat-diff {{ $item['avg_mmr_gain_diff'] > 0 ? 'positive' : ($item['avg_mmr_gain_diff'] < 0 ? 'negative' : 'neutral') }}">
                                {{ $item['avg_mmr_gain_diff'] > 0 ? '+' : '' }}{{ number_format($item['avg_mmr_gain_diff'], 1) }}
                            </span>
                        </div>
                        <div class="stat-detail">
                            {{ number_format($item['previous']->avg_mmr_gain, 1) }} → {{ number_format($item['latest']->avg_mmr_gain, 1) }}
                        </div>
                    </td>
                    <td class="text-center">
                        <div>
                            <span class="stat-diff {{ $item['win_rate_diff'] > 0 ? 'positive' : ($item['win_rate_diff'] < 0 ? 'negative' : 'neutral') }}">
                                {{ $item['win_rate_diff'] > 0 ? '+' : '' }}{{ number_format($item['win_rate_diff'], 2) }}%
                            </span>
                        </div>
                        <div class="stat-detail">
                            {{ number_format($item['previous']->top1_count_percent, 2) }}% → {{ number_format($item['latest']->top1_count_percent, 2) }}%
                        </div>
                    </td>
                    <td class="hide-on-mobile text-center">
                        @php
                            $top2_diff = $item['latest']->top2_count_percent - $item['previous']->top2_count_percent;
                        @endphp
                        <div>
                            <span class="stat-diff {{ $top2_diff > 0 ? 'positive' : ($top2_diff < 0 ? 'negative' : 'neutral') }}">
                                {{ $top2_diff > 0 ? '+' : '' }}{{ number_format($top2_diff, 2) }}%
                            </span>
                        </div>
                        <div class="stat-detail">
                            {{ number_format($item['previous']->top2_count_percent, 2) }}% → {{ number_format($item['latest']->top2_count_percent, 2) }}%
                        </div>
                    </td>
                    <td class="hide-on-mobile text-center">
                        <div>
                            <span class="stat-diff {{ $item['top4_rate_diff'] > 0 ? 'positive' : ($item['top4_rate_diff'] < 0 ? 'negative' : 'neutral') }}">
                                {{ $item['top4_rate_diff'] > 0 ? '+' : '' }}{{ number_format($item['top4_rate_diff'], 2) }}%
                            </span>
                        </div>
                        <div class="stat-detail">
                            {{ number_format($item['previous']->top4_count_percent, 2) }}% → {{ number_format($item['latest']->top4_count_percent, 2) }}%
                        </div>
                    </td>
                    <td class="hide-on-mobile hide-on-tablet text-center">
                        @php
                            $endgame_diff = $item['latest']->endgame_win_percent - $item['previous']->endgame_win_percent;
                        @endphp
                        <div>
                            <span class="stat-diff {{ $endgame_diff > 0 ? 'positive' : ($endgame_diff < 0 ? 'negative' : 'neutral') }}">
                                {{ $endgame_diff > 0 ? '+' : '' }}{{ number_format($endgame_diff, 2) }}%
                            </span>
                        </div>
                        <div class="stat-detail">
                            {{ number_format($item['previous']->endgame_win_percent, 2) }}% → {{ number_format($item['latest']->endgame_win_percent, 2) }}%
                        </div>
                    </td>
                    <td class="hide-on-mobile text-center">
                        @php
                            $tk_diff = $item['latest']->avg_team_kill_score - $item['previous']->avg_team_kill_score;
                        @endphp
                        <div>
                            <span class="stat-diff {{ $tk_diff > 0 ? 'positive' : ($tk_diff < 0 ? 'negative' : 'neutral') }}">
                                {{ $tk_diff > 0 ? '+' : '' }}{{ number_format($tk_diff, 2) }}
                            </span>
                        </div>
                        <div class="stat-detail">
                            {{ number_format($item['previous']->avg_team_kill_score, 2) }} → {{ number_format($item['latest']->avg_team_kill_score, 2) }}
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p class="empty-message">
            너프된 캐릭터가 없습니다.
        </p>
        @endif
    </div>

    @else
    <div class="no-data-message">
        <p>비교할 버전 데이터가 없습니다.</p>
    </div>
    @endif
</div>
@endsection

@push('styles')
<style>
    /* 버전 비교 박스 */
    .version-info-box {
        margin-bottom: 20px;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 5px;
    }

    .version-info-box h3 {
        margin: 0 0 10px 0;
    }

    .version-info-box p {
        margin: 5px 0;
    }

    .version-info-note {
        color: #666;
    }

    /* 섹션 스타일 */
    .section-container {
        margin-bottom: 30px;
    }

    .section-title {
        margin-bottom: 15px;
    }

    .section-title.buffed {
        color: #28a745;
    }

    .section-title.nerfed {
        color: #dc3545;
    }

    /* 테이블 스타일 */
    .patch-table {
        width: 100%;
        border-collapse: collapse;
    }

    .patch-table thead tr {
        border-bottom: 2px solid;
    }

    .patch-table.buffed thead tr {
        background-color: #d4edda;
        border-bottom-color: #28a745;
    }

    .patch-table.nerfed thead tr {
        background-color: #f8d7da;
        border-bottom-color: #dc3545;
    }

    .patch-table th {
        padding: 10px;
    }

    .patch-table th.text-left {
        text-align: left;
    }

    .patch-table th.text-center {
        text-align: center;
    }

    .patch-table tbody tr {
        border-bottom: 1px solid #ddd;
        cursor: pointer;
    }

    .patch-table tbody tr:nth-child(even) {
        background-color: #f8f9fa;
    }

    .patch-table tbody tr:nth-child(odd) {
        background-color: white;
    }

    .patch-table td {
        padding: 10px;
    }

    .patch-table td.text-center {
        text-align: center;
    }

    /* 캐릭터 셀 */
    .character-cell-content {
        display: flex;
        align-items: center;
    }

    /* 티어 변동 */
    .tier-change-container {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
    }

    .tier-badge-small {
        font-size: 11px;
        padding: 2px 6px;
    }

    .tier-arrow {
        color: #28a745;
    }

    .tier-arrow.nerfed {
        color: #dc3545;
    }

    .meta-score-detail {
        font-size: 11px;
        color: #666;
        margin-top: 4px;
    }

    .meta-score-diff {
        font-size: 10px;
        margin-top: 2px;
    }

    .meta-score-diff.positive {
        color: #28a745;
    }

    .meta-score-diff.negative {
        color: #dc3545;
    }

    .meta-score-diff.neutral {
        color: #666;
    }

    /* 점수 변동 관련 스타일 */
    .stat-diff {
        font-weight: bold;
    }

    .stat-diff.positive {
        color: #28a745;
    }

    .stat-diff.negative {
        color: #dc3545;
    }

    .stat-diff.neutral {
        color: #666;
    }

    .stat-detail {
        font-size: 12px;
        color: #666;
    }

    /* 캐릭터 컬럼 최소 너비 */
    .patch-table th:first-child,
    .patch-table td:first-child {
        min-width: 150px;
    }

    /* 빈 데이터 메시지 */
    .empty-message {
        color: #666;
        padding: 20px;
        text-align: center;
        background-color: #f8f9fa;
        border-radius: 5px;
    }

    .no-data-message {
        padding: 40px;
        text-align: center;
        background-color: #f8f9fa;
        border-radius: 5px;
    }

    .no-data-message p {
        color: #666;
        font-size: 16px;
    }

    /* 태블릿 환경 (768px ~ 1024px) - 막금구승률 숨김 */
    @media (max-width: 1024px) and (min-width: 769px) {
        .hide-on-tablet {
            display: none !important;
        }
    }

    /* 모바일 환경 (768px 이하) - TOP2, TOP4, 막금구승률 숨김 */
    @media (max-width: 768px) {
        .hide-on-mobile {
            display: none !important;
        }

        /* 캐릭터 컬럼 최소 너비 줄이기 */
        .patch-table th:first-child,
        .patch-table td:first-child {
            min-width: 60px;
        }

        /* 캐릭터 셀 세로 정렬 */
        .character-cell-content {
            flex-direction: column !important;
            align-items: center !important;
            text-align: center !important;
        }

        .icon-container {
            margin-right: 0 !important;
            margin-bottom: 8px !important;
        }

        .character-name-weapon {
            text-align: center !important;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // 캐릭터 행 클릭시 상세 페이지로 이동
    document.addEventListener('DOMContentLoaded', function() {
        const rows = document.querySelectorAll('tr[data-href]');
        rows.forEach(row => {
            row.addEventListener('click', function() {
                window.location.href = this.dataset.href;
            });
        });
    });
</script>
@endpush
