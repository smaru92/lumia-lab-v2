@extends('layouts.app')

@section('title', '메인 | 아글라이아 연구소')

@push('styles')
    <style>
        /* 페이지 링크 카드 스타일 */
        .page-links-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .page-link-card {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .page-link-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0);
            transition: background 0.3s ease;
        }

        .page-link-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }

        .page-link-card:hover::before {
            background: rgba(255, 255, 255, 0.08);
        }

        .page-link-card.character {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        }

        .page-link-card.equipment {
            background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
        }

        .page-link-card.equipment-first {
            background: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%);
        }

        .page-link-icon {
            font-size: 28px;
            margin-bottom: 6px;
            position: relative;
            z-index: 1;
        }

        .page-link-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 4px;
            position: relative;
            z-index: 1;
        }

        .page-link-desc {
            font-size: 12px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        /* 카드용 Beta 배지 */
        .card-beta-badge {
            position: absolute;
            bottom: 6px;
            right: 6px;
            font-size: 9px;
            font-weight: bold;
            color: #fff;
            background-color: #ff6b35;
            padding: 2px 5px;
            border-radius: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.2;
            z-index: 2;
        }

        /* 탭 메뉴 스타일 */
        .patch-tabs {
            display: flex;
            border: 1px solid #444;
            background-color: #1a1a1a;
            margin-bottom: 20px;
            gap: 0;
        }

        .patch-tab-button {
            padding: 12px 24px;
            background-color: #1a1a1a;
            border: none;
            border-right: 1px solid #444;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            color: #e0e0e0;
            border-radius: 0;
            transition: all 0.3s ease;
            position: relative;
        }

        .patch-tab-button:last-child {
            border-right: none;
        }

        .patch-tab-button:hover {
            color: #fff;
            background-color: #333;
        }

        .patch-tab-button.active {
            color: #fff;
            background-color: #333;
            border-bottom: 2px solid #fff;
        }

        .patch-tab-button.active.buffed {
            color: #28a745;
            border-bottom: 2px solid #28a745;
        }

        .patch-tab-button.active.nerfed {
            color: #dc3545;
            border-bottom: 2px solid #dc3545;
        }

        .patch-tab-badge {
            display: inline-block;
            background-color: #333;
            color: #ccc;
            padding: 2px 8px;
            border-radius: 0;
            font-size: 12px;
            margin-left: 6px;
        }

        .patch-tab-button.active .patch-tab-badge {
            background-color: #444;
            color: #fff;
        }

        .patch-tab-button.active.buffed .patch-tab-badge {
            background-color: rgba(40, 167, 69, 0.3);
            color: #28a745;
        }

        .patch-tab-button.active.nerfed .patch-tab-badge {
            background-color: rgba(220, 53, 69, 0.3);
            color: #dc3545;
        }

        .patch-tab-content {
            display: none;
        }

        .patch-tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* 티어 선택기 스타일 (캐릭터 통계 페이지와 동일) */
        .tier-selector-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }

        .tier-selector-container .custom-dropdown-container {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tier-selector-container .custom-dropdown-container label {
            margin-bottom: 0;
        }

        /* 로딩/에러 상태 */
        .loading-container,
        .error-container {
            padding: 40px 20px;
            text-align: center;
            color: #666;
        }

        .error-container {
            color: #d32f2f;
        }

        /* 반응형 디자인 */
        @media (max-width: 599px) {
            .page-links-container {
                grid-template-columns: repeat(3, 1fr);
                gap: 8px;
            }

            .page-link-card {
                padding: 10px 8px;
            }

            .page-link-icon {
                font-size: 20px;
                margin-bottom: 4px;
            }

            .page-link-title {
                font-size: 13px;
                margin-bottom: 2px;
            }

            .page-link-desc {
                font-size: 10px;
            }

            .patch-tabs {
                gap: 5px;
            }

            .patch-tab-button {
                padding: 10px 16px;
                font-size: 14px;
            }
        }

        @media (min-width: 600px) and (max-width: 1024px) {
            .page-links-container {
                grid-template-columns: repeat(3, 1fr);
            }
        }
    </style>
@endpush

@section('content')
<div class="container">
    <!-- 페이지 링크 카드 -->
    <div class="page-links-container">
        <a href="/character" class="page-link-card character">
            <div class="page-link-icon">🎭</div>
            <div class="page-link-title">캐릭터 통계</div>
            <div class="page-link-desc">캐릭터별 승률 및 통계</div>
        </a>
        <a href="/equipment" class="page-link-card equipment">
            <span class="card-beta-badge">Beta</span>
            <div class="page-link-icon">⚔️</div>
            <div class="page-link-title">장비 통계</div>
            <div class="page-link-desc">장비 아이템 통계</div>
        </a>
        <a href="/equipment-first" class="page-link-card equipment-first">
            <span class="card-beta-badge">Beta</span>
            <div class="page-link-icon">🛡️</div>
            <div class="page-link-title">초기 장비 통계</div>
            <div class="page-link-desc">초기 장비 아이템 통계</div>
        </a>
    </div>

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
        <div class="tier-selector-container">
            {{-- 최소 티어 드롭다운 (캐릭터 통계 페이지와 동일) --}}
            <div class="custom-dropdown-container">
                <label><strong>기준 티어</strong></label>
                <div class="custom-dropdown" id="main-tier-dropdown">
                    @php
                        $selectedTier = $availableTiers[$minTier] ?? $availableTiers['Meteorite'];
                    @endphp
                    <div class="dropdown-selected" data-value="{{ $minTier }}">
                        @if($selectedTier['icon'])
                            <span class="tier-icon" style="background-image: url('{{ asset('storage/Tier/icon/' . $selectedTier['icon'] . '.png') }}');" aria-label="{{ $selectedTier['name'] }}"></span>
                        @endif
                        <span>{{ $selectedTier['name'] }}</span>
                    </div>
                    <div class="dropdown-options">
                        @foreach($availableTiers as $value => $tier)
                            <div class="dropdown-option {{ $minTier === $value ? 'selected' : '' }}" data-value="{{ $value }}">
                                @if($tier['icon'])
                                    <span class="tier-icon" style="background-image: url('{{ asset('storage/Tier/icon/' . $tier['icon'] . '.png') }}');" aria-label="{{ $tier['name'] }}"></span>
                                @endif
                                <span>{{ $tier['name'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 탭 메뉴 -->
    <div class="patch-tabs">
        <button class="patch-tab-button buffed active" data-tab="buffed">
            🔼 버프
            <span class="patch-tab-badge">{{ $buffedCharacters->count() }}</span>
        </button>
        <button class="patch-tab-button nerfed" data-tab="nerfed">
            🔽 너프
            <span class="patch-tab-badge">{{ $nerfedCharacters->count() }}</span>
        </button>
    </div>

    <!-- 버프된 캐릭터 탭 컨텐츠 -->
    <div id="buffed-tab" class="patch-tab-content active">
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
                    <th class="text-center">TOP2</th>
                    <th class="text-center">TOP4</th>
                    <th class="text-center">막금구승률</th>
                    <th class="text-center">평균TK</th>
                </tr>
            </thead>
            <tbody>
                @foreach($buffedCharacters as $index => $item)
                <tr data-href="/detail/{{ $item['character_name'] }}-{{ $item['weapon_type_en'] ?? $item['weapon_type'] }}?min_tier={{ $minTier }}&version={{ $latestVersion->version_season }}.{{ $latestVersion->version_major }}.{{ $latestVersion->version_minor }}" class="buffed-row {{ $index >= 5 ? 'hidden-row' : '' }}">
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
                            <div class="character-name-weapon streak-name-row">
                                <div>
                                    {{ $item['character_name'] }}<br>
                                    @if($weaponType && $weaponType !== 'All')
                                    <small>{{ $weaponType }}</small>
                                    @endif
                                </div>
                                @if(($item['streak'] ?? 0) >= 2)
                                <span class="streak-badge streak-{{ $item['streak_type'] === '버프' ? 'buff' : 'nerf' }}">{{ $item['streak'] }}연속 {{ $item['streak_type'] }}</span>
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
                    <td class="text-center">
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
                    <td class="text-center">
                        <div>
                            <span class="stat-diff {{ $item['top4_rate_diff'] > 0 ? 'positive' : ($item['top4_rate_diff'] < 0 ? 'negative' : 'neutral') }}">
                                {{ $item['top4_rate_diff'] > 0 ? '+' : '' }}{{ number_format($item['top4_rate_diff'], 2) }}%
                            </span>
                        </div>
                        <div class="stat-detail">
                            {{ number_format($item['previous']->top4_count_percent, 2) }}% → {{ number_format($item['latest']->top4_count_percent, 2) }}%
                        </div>
                    </td>
                    <td class="text-center">
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
                    <td class="text-center">
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
    </div>

    <!-- 너프된 캐릭터 탭 컨텐츠 -->
    <div id="nerfed-tab" class="patch-tab-content">
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
                    <th class="text-center">TOP2</th>
                    <th class="text-center">TOP4</th>
                    <th class="text-center">막금구승률</th>
                    <th class="text-center">평균TK</th>
                </tr>
            </thead>
            <tbody>
                @foreach($nerfedCharacters as $index => $item)
                <tr data-href="/detail/{{ $item['character_name'] }}-{{ $item['weapon_type_en'] ?? $item['weapon_type'] }}?min_tier={{ $minTier }}&version={{ $latestVersion->version_season }}.{{ $latestVersion->version_major }}.{{ $latestVersion->version_minor }}" class="nerfed-row {{ $index >= 5 ? 'hidden-row' : '' }}">
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
                            <div class="character-name-weapon streak-name-row">
                                <div>
                                    {{ $item['character_name'] }}<br>
                                    @if($weaponType && $weaponType !== 'All')
                                    <small>{{ $weaponType }}</small>
                                    @endif
                                </div>
                                @if(($item['streak'] ?? 0) >= 2)
                                <span class="streak-badge streak-{{ $item['streak_type'] === '버프' ? 'buff' : 'nerf' }}">{{ $item['streak'] }}연속 {{ $item['streak_type'] }}</span>
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
                    <td class="text-center">
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
                    <td class="text-center">
                        <div>
                            <span class="stat-diff {{ $item['top4_rate_diff'] > 0 ? 'positive' : ($item['top4_rate_diff'] < 0 ? 'negative' : 'neutral') }}">
                                {{ $item['top4_rate_diff'] > 0 ? '+' : '' }}{{ number_format($item['top4_rate_diff'], 2) }}%
                            </span>
                        </div>
                        <div class="stat-detail">
                            {{ number_format($item['previous']->top4_count_percent, 2) }}% → {{ number_format($item['latest']->top4_count_percent, 2) }}%
                        </div>
                    </td>
                    <td class="text-center">
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
                    <td class="text-center">
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

        // 탭 메뉴 기능
        const tabButtons = document.querySelectorAll('.patch-tab-button');
        const tabContents = document.querySelectorAll('.patch-tab-content');

        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const tabName = this.getAttribute('data-tab');

                // 모든 탭 버튼에서 active 제거
                tabButtons.forEach(btn => btn.classList.remove('active'));

                // 모든 탭 컨텐츠 숨기기
                tabContents.forEach(content => content.classList.remove('active'));

                // 클릭한 탭 버튼 활성화
                this.classList.add('active');

                // 해당 탭 컨텐츠 표시
                const targetTab = document.getElementById(tabName + '-tab');
                if (targetTab) {
                    targetTab.classList.add('active');
                }
            });
        });

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

    // 현재 선택된 티어
    let currentTier = '{{ $minTier }}';
    let latestVersionInfo = @json($latestVersion ? ['full_version' => $latestVersion->version_season . '.' . $latestVersion->version_major . '.' . $latestVersion->version_minor] : null);

    // 티어 드롭다운 초기화
    const mainTierDropdown = document.getElementById('main-tier-dropdown');
    if (mainTierDropdown) {
        const dropdownSelected = mainTierDropdown.querySelector('.dropdown-selected');
        const dropdownOptions = mainTierDropdown.querySelector('.dropdown-options');

        // 드롭다운 토글
        dropdownSelected.addEventListener('click', function(e) {
            e.stopPropagation();
            mainTierDropdown.classList.toggle('open');
        });

        // 옵션 클릭
        dropdownOptions.querySelectorAll('.dropdown-option').forEach(option => {
            option.addEventListener('click', function() {
                const value = this.dataset.value;
                const html = this.innerHTML;

                // 선택된 값 업데이트
                dropdownSelected.innerHTML = html;
                dropdownSelected.dataset.value = value;

                // 선택 상태 업데이트
                dropdownOptions.querySelectorAll('.dropdown-option').forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');

                // 드롭다운 닫기
                mainTierDropdown.classList.remove('open');

                // 티어 변경 함수 호출
                changeTier(value);
            });
        });

        // 외부 클릭시 드롭다운 닫기
        document.addEventListener('click', function() {
            mainTierDropdown.classList.remove('open');
        });
    }

    // 티어 변경 함수 (비동기)
    async function changeTier(tier) {
        if (tier === currentTier) return;

        const buffedTab = document.getElementById('buffed-tab');
        const nerfedTab = document.getElementById('nerfed-tab');
        const buffedBadge = document.querySelector('.patch-tab-button.buffed .patch-tab-badge');
        const nerfedBadge = document.querySelector('.patch-tab-button.nerfed .patch-tab-badge');

        // 로딩 상태 표시
        buffedTab.innerHTML = '<div class="loading-container"><p>로딩 중...</p></div>';
        nerfedTab.innerHTML = '<div class="loading-container"><p>로딩 중...</p></div>';

        try {
            const response = await fetch(`/api/patch-comparison?min_tier=${tier}`);
            if (!response.ok) throw new Error('API 호출 실패');

            const data = await response.json();

            // 버프/너프 테이블 렌더링
            renderBuffedTable(data.buffedCharacters, data.latestVersion, tier);
            renderNerfedTable(data.nerfedCharacters, data.latestVersion, tier);

            // 배지 업데이트
            buffedBadge.textContent = data.buffedCharacters.length;
            nerfedBadge.textContent = data.nerfedCharacters.length;

            // URL 업데이트 (히스토리에 추가)
            const url = new URL(window.location.href);
            url.searchParams.set('min_tier', tier);
            history.pushState({ tier: tier }, '', url.href);

            currentTier = tier;
            latestVersionInfo = data.latestVersion;

        } catch (error) {
            console.error('Error:', error);
            buffedTab.innerHTML = '<div class="error-container"><p>데이터를 불러오는데 실패했습니다.</p></div>';
            nerfedTab.innerHTML = '<div class="error-container"><p>데이터를 불러오는데 실패했습니다.</p></div>';
        }
    }

    // 버프 테이블 렌더링
    function renderBuffedTable(characters, version, tier) {
        const container = document.getElementById('buffed-tab');
        const title = document.querySelector('.section-title.buffed');

        if (title) {
            title.innerHTML = `🔼 버프된 캐릭터 (${characters.length}개)`;
        }

        if (characters.length === 0) {
            container.innerHTML = '<div class="section-container"><h3 class="section-title buffed">🔼 버프된 캐릭터 (0개)</h3><p class="empty-message">버프된 캐릭터가 없습니다.</p></div>';
            return;
        }

        container.innerHTML = renderCharacterTable(characters, version, tier, 'buffed');
        setupTableEvents('buffed', characters.length);
    }

    // 너프 테이블 렌더링
    function renderNerfedTable(characters, version, tier) {
        const container = document.getElementById('nerfed-tab');
        const title = document.querySelector('.section-title.nerfed');

        if (title) {
            title.innerHTML = `🔽 너프된 캐릭터 (${characters.length}개)`;
        }

        if (characters.length === 0) {
            container.innerHTML = '<div class="section-container"><h3 class="section-title nerfed">🔽 너프된 캐릭터 (0개)</h3><p class="empty-message">너프된 캐릭터가 없습니다.</p></div>';
            return;
        }

        container.innerHTML = renderCharacterTable(characters, version, tier, 'nerfed');
        setupTableEvents('nerfed', characters.length);
    }

    // 캐릭터 테이블 HTML 생성
    function renderCharacterTable(characters, version, tier, type) {
        const typeKo = type === 'buffed' ? '버프' : '너프';
        const emoji = type === 'buffed' ? '🔼' : '🔽';

        let html = `<div class="section-container">
            <h3 class="section-title ${type}">${emoji} ${typeKo}된 캐릭터 (${characters.length}개)</h3>
            <div class="table-wrapper">
            <table id="${type}Table" class="patch-table ${type}">
                <thead>
                    <tr>
                        <th class="text-left">캐릭터</th>
                        <th class="text-center">티어 변동</th>
                        <th class="text-center">픽률</th>
                        <th class="text-center">평균 획득점수</th>
                        <th class="text-center">승률</th>
                        <th class="text-center">TOP2</th>
                        <th class="text-center">TOP4</th>
                        <th class="text-center">막금구승률</th>
                        <th class="text-center">평균TK</th>
                    </tr>
                </thead>
                <tbody>`;

        characters.forEach((item, index) => {
            const hiddenClass = index >= 5 ? 'hidden-row' : '';
            const charId = String(item.character_id).padStart(3, '0');
            const weaponTypeEn = item.weapon_type_en || item.weapon_type;
            const detailUrl = `/detail/${item.character_name}-${weaponTypeEn}?min_tier=${tier}&version=${version.full_version}`;

            const prevTierClass = 'tier-' + item.previous.meta_tier.toLowerCase().replace(' ', '-');
            const latestTierClass = 'tier-' + item.latest.meta_tier.toLowerCase().replace(' ', '-');

            const top2Diff = item.latest.top2_count_percent - item.previous.top2_count_percent;
            const endgameDiff = item.latest.endgame_win_percent - item.previous.endgame_win_percent;
            const tkDiff = item.latest.avg_team_kill_score - item.previous.avg_team_kill_score;

            const streakBadge = (item.streak >= 2 && item.streak_type)
                ? `<span class="streak-badge streak-${item.streak_type === '버프' ? 'buff' : 'nerf'}">${item.streak}연속 ${item.streak_type}</span>`
                : '';

            html += `
                <tr data-href="${detailUrl}" class="${type}-row ${hiddenClass}">
                    <td>
                        <div class="character-cell-content">
                            <div class="icon-container">
                                <img src="/storage/Character/icon/${charId}.png" alt="${item.character_name}" class="character-icon" loading="lazy" onerror="this.src='/storage/Character/icon/default.png';">
                                ${item.weapon_type !== 'All' ? `<img src="/storage/Weapon/${weaponTypeEn}.png" alt="${item.weapon_type}" class="weapon-icon" loading="lazy" onerror="this.src='/storage/Weapon/icon/default.png';">` : ''}
                            </div>
                            <div class="character-name-weapon streak-name-row">
                                <div>
                                    ${item.character_name}<br>
                                    ${item.weapon_type && item.weapon_type !== 'All' ? `<small>${item.weapon_type}</small>` : ''}
                                </div>
                                ${streakBadge}
                            </div>
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="tier-change-container">
                            <span class="tier-badge tier-badge-small ${prevTierClass}">${item.previous.meta_tier}</span>
                            <span class="tier-arrow">→</span>
                            <span class="tier-badge tier-badge-small ${latestTierClass}">${item.latest.meta_tier}</span>
                        </div>
                        <div class="meta-score-detail">${formatNumber(item.previous.meta_score, 2)} → ${formatNumber(item.latest.meta_score, 2)}</div>
                        <div class="meta-score-diff ${getDiffClass(item.meta_score_diff)}">${formatDiff(item.meta_score_diff, 2)}</div>
                    </td>
                    <td class="text-center">
                        <div><span class="stat-diff ${getDiffClass(item.pick_rate_diff)}">${formatDiff(item.pick_rate_diff, 2)}%</span></div>
                        <div class="stat-detail">${formatNumber(item.previous.game_count_percent, 2)}% → ${formatNumber(item.latest.game_count_percent, 2)}%</div>
                    </td>
                    <td class="text-center">
                        <div><span class="stat-diff ${getDiffClass(item.avg_mmr_gain_diff)}">${formatDiff(item.avg_mmr_gain_diff, 1)}</span></div>
                        <div class="stat-detail">${formatNumber(item.previous.avg_mmr_gain, 1)} → ${formatNumber(item.latest.avg_mmr_gain, 1)}</div>
                    </td>
                    <td class="text-center">
                        <div><span class="stat-diff ${getDiffClass(item.win_rate_diff)}">${formatDiff(item.win_rate_diff, 2)}%</span></div>
                        <div class="stat-detail">${formatNumber(item.previous.top1_count_percent, 2)}% → ${formatNumber(item.latest.top1_count_percent, 2)}%</div>
                    </td>
                    <td class="text-center">
                        <div><span class="stat-diff ${getDiffClass(top2Diff)}">${formatDiff(top2Diff, 2)}%</span></div>
                        <div class="stat-detail">${formatNumber(item.previous.top2_count_percent, 2)}% → ${formatNumber(item.latest.top2_count_percent, 2)}%</div>
                    </td>
                    <td class="text-center">
                        <div><span class="stat-diff ${getDiffClass(item.top4_rate_diff)}">${formatDiff(item.top4_rate_diff, 2)}%</span></div>
                        <div class="stat-detail">${formatNumber(item.previous.top4_count_percent, 2)}% → ${formatNumber(item.latest.top4_count_percent, 2)}%</div>
                    </td>
                    <td class="text-center">
                        <div><span class="stat-diff ${getDiffClass(endgameDiff)}">${formatDiff(endgameDiff, 2)}%</span></div>
                        <div class="stat-detail">${formatNumber(item.previous.endgame_win_percent, 2)}% → ${formatNumber(item.latest.endgame_win_percent, 2)}%</div>
                    </td>
                    <td class="text-center">
                        <div><span class="stat-diff ${getDiffClass(tkDiff)}">${formatDiff(tkDiff, 2)}</span></div>
                        <div class="stat-detail">${formatNumber(item.previous.avg_team_kill_score, 2)} → ${formatNumber(item.latest.avg_team_kill_score, 2)}</div>
                    </td>
                </tr>`;
        });

        html += `</tbody></table></div>`;

        if (characters.length > 5) {
            html += `<div class="view-all-container"><button id="${type}ViewAll" class="view-all-btn">전체보기 (${characters.length}개)</button></div>`;
        }

        html += '</div>';
        return html;
    }

    // 테이블 이벤트 설정
    function setupTableEvents(type, count) {
        // 행 클릭 이벤트
        const rows = document.querySelectorAll(`#${type}-tab tr[data-href]`);
        rows.forEach(row => {
            row.addEventListener('click', function() {
                window.location.href = this.dataset.href;
            });
        });

        // 전체보기 버튼
        const viewAllBtn = document.getElementById(`${type}ViewAll`);
        if (viewAllBtn) {
            viewAllBtn.addEventListener('click', function() {
                const hiddenRows = document.querySelectorAll(`.${type}-row.hidden-row`);

                if (hiddenRows.length > 0) {
                    hiddenRows.forEach(row => row.classList.remove('hidden-row'));
                    this.textContent = '접기';
                    this.classList.add('collapse');
                } else {
                    document.querySelectorAll(`.${type}-row`).forEach((row, index) => {
                        if (index >= 5) row.classList.add('hidden-row');
                    });
                    this.textContent = `전체보기 (${count}개)`;
                    this.classList.remove('collapse');
                }
            });
        }
    }

    // 헬퍼 함수들
    function formatNumber(value, decimals) {
        return Number(value).toLocaleString('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    function formatDiff(value, decimals) {
        const formatted = formatNumber(value, decimals);
        return value > 0 ? '+' + formatted : formatted;
    }

    function getDiffClass(value) {
        if (value > 0) return 'positive';
        if (value < 0) return 'negative';
        return 'neutral';
    }

    // 브라우저 뒤로가기/앞으로가기 처리
    window.addEventListener('popstate', function(event) {
        if (event.state && event.state.tier) {
            document.getElementById('tier-selector').value = event.state.tier;
            changeTier(event.state.tier);
        }
    });
</script>
@endpush
