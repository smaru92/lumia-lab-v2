@extends('layouts.app')

@section('title', '메인 | 아글라이아 연구소')

@section('content')
<div class="container">
    <!-- 사이트 안내문구 -->
    <div class="notice-box" id="noticeBox">
        <button class="notice-close-btn" id="noticeCloseBtn" aria-label="안내 닫기">&times;</button>
        <div class="notice-header">
            <svg class="notice-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="notice-title">안내</h3>
        </div>
        <ul>
            <li>본 사이트는 이터널리턴(Eternal Return) 게임의 실험체 및 아이템 통계를 다루는 비공식 사이트입니다.</li>
            <li>데이터의 완전성과 정확성이 보증되지 않습니다. 사이트 내용을 악용하지 말아 주십시오.</li>
            <li>데이터 갱신은 1시간~2시간 마다 한번씩 이뤄집니다.</li>
            <li>이 사이트는 PC 화면 크기에 최적화되어 있습니다. 모바일 환경에서는 일부 기능이 제한될 수 있습니다.</li>
            <li>사이트 관련 피드백은 <a href="mailto:aglaia.lumia@gmail.com">aglaia.lumia@gmail.com</a>으로 연락주시길 바랍니다.</li>
        </ul>
    </div>

    <h2><a href="/">패치노트 영향 분석</a></h2>

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
        <div class="table-wrapper">
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
                @foreach($buffedCharacters as $index => $item)
                <tr data-href="/detail/{{ $item['character_name'] }}-{{ $item['weapon_type_en'] ?? $item['weapon_type'] }}?min_tier=Diamond&version={{ $latestVersion->version_season }}.{{ $latestVersion->version_major }}.{{ $latestVersion->version_minor }}" class="buffed-row {{ $index >= 5 ? 'hidden-row' : '' }}">
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
        </div>
        @if($buffedCharacters->count() > 5)
        <div class="view-all-container">
            <button id="buffedViewAll" class="view-all-btn">
                전체보기 ({{ $buffedCharacters->count() }}개)
            </button>
        </div>
        @endif
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
        <div class="table-wrapper">
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
                @foreach($nerfedCharacters as $index => $item)
                <tr data-href="/detail/{{ $item['character_name'] }}-{{ $item['weapon_type_en'] ?? $item['weapon_type'] }}?min_tier=Diamond&version={{ $latestVersion->version_season }}.{{ $latestVersion->version_major }}.{{ $latestVersion->version_minor }}" class="nerfed-row {{ $index >= 5 ? 'hidden-row' : '' }}">
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
        </div>
        @if($nerfedCharacters->count() > 5)
        <div class="view-all-container">
            <button id="nerfedViewAll" class="view-all-btn">
                전체보기 ({{ $nerfedCharacters->count() }}개)
            </button>
        </div>
        @endif
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


@push('scripts')
<script>
    // 안내문구 닫기 버튼
    document.addEventListener('DOMContentLoaded', function() {
        const noticeBox = document.getElementById('noticeBox');
        const closeBtn = document.getElementById('noticeCloseBtn');

        if (closeBtn && noticeBox) {
            closeBtn.addEventListener('click', function() {
                noticeBox.style.display = 'none';
                // 로컬 스토리지에 닫힌 상태 저장
                localStorage.setItem('noticeBoxClosed', 'true');
            });

            // 페이지 로드 시 닫힌 상태 확인
            if (localStorage.getItem('noticeBoxClosed') === 'true') {
                noticeBox.style.display = 'none';
            }
        }

        // 캐릭터 행 클릭시 상세 페이지로 이동
        const rows = document.querySelectorAll('tr[data-href]');
        rows.forEach(row => {
            row.addEventListener('click', function() {
                window.location.href = this.dataset.href;
            });
        });

        // 버프된 캐릭터 전체보기 버튼
        const buffedViewAllBtn = document.getElementById('buffedViewAll');
        if (buffedViewAllBtn) {
            buffedViewAllBtn.addEventListener('click', function() {
                const hiddenRows = document.querySelectorAll('.buffed-row.hidden-row');

                if (hiddenRows.length > 0) {
                    // 펼치기 - 숨겨진 행이 있으면
                    hiddenRows.forEach(row => {
                        row.classList.remove('hidden-row');
                    });
                    this.textContent = '접기';
                    this.classList.add('collapse');
                } else {
                    // 접기 - 모두 보이는 상태면
                    document.querySelectorAll('.buffed-row').forEach((row, index) => {
                        if (index >= 5) {
                            row.classList.add('hidden-row');
                        }
                    });
                    this.textContent = '전체보기 ({{ $buffedCharacters->count() }}개)';
                    this.classList.remove('collapse');
                }
            });
        }

        // 너프된 캐릭터 전체보기 버튼
        const nerfedViewAllBtn = document.getElementById('nerfedViewAll');
        if (nerfedViewAllBtn) {
            nerfedViewAllBtn.addEventListener('click', function() {
                const hiddenRows = document.querySelectorAll('.nerfed-row.hidden-row');

                if (hiddenRows.length > 0) {
                    // 펼치기 - 숨겨진 행이 있으면
                    hiddenRows.forEach(row => {
                        row.classList.remove('hidden-row');
                    });
                    this.textContent = '접기';
                    this.classList.add('collapse');
                } else {
                    // 접기 - 모두 보이는 상태면
                    document.querySelectorAll('.nerfed-row').forEach((row, index) => {
                        if (index >= 5) {
                            row.classList.add('hidden-row');
                        }
                    });
                    this.textContent = '전체보기 ({{ $nerfedCharacters->count() }}개)';
                    this.classList.remove('collapse');
                }
            });
        }
    });
</script>
@endpush
