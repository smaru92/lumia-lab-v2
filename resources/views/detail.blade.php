@extends('layouts.app')

@section('title', '상세 통계 | 아글라이아 연구소')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/detail.css') }}?v={{ time() }}">
@endpush

@section('content')
<div class="container">
    <h2><a href="/character?min_tier={{ request('min_tier') }}&version={{ request('version') }}">게임 통계</a></h2>

    {{-- Keep filters for changing options --}}
    <div class="detail-filter-container">
        <div style="display: flex; flex-direction: column; align-items: center;">
            <label for="sel-version-filter" style="margin-bottom: 5px;"><strong>버전</strong></label>
            <select id="sel-version-filter"> {{-- Changed ID to avoid conflict --}}
                @foreach($versions as $version)
                    <option value="{{ $version }}" {{ request('version', $defaultVersion) === $version ? 'selected' : '' }}>{{ $version }}</option>
                @endforeach
            </select>
        </div>
        <div style="display: flex; flex-direction: column; align-items: center;">
            <label for="sel-tier-filter" style="margin-bottom: 5px;"><strong>최소 티어</strong></label>
            <select id="sel-tier-filter"> {{-- Changed ID to avoid conflict --}}
                <option value="All" {{ request('min_tier', $defaultTier) === 'All' ? 'selected' : '' }}>전체</option>
                <option value="Platinum" {{ request('min_tier', $defaultTier) === 'Platinum' ? 'selected' : '' }}>플레티넘</option>
                <option value="Diamond" {{ request('min_tier', $defaultTier) === 'Diamond' ? 'selected' : '' }}>다이아</option>
                <option value="Diamond2" {{ request('min_tier', $defaultTier) === 'Diamond2' ? 'selected' : '' }}>다이아2</option>
                <option value="Meteorite" {{ request('min_tier', $defaultTier) === 'Meteorite' ? 'selected' : '' }}>메테오라이트</option>
                <option value="Mithril" {{ request('min_tier', $defaultTier) === 'Mithril' ? 'selected' : '' }}>미스릴</option>
                <option value="Top" {{ request('min_tier', $defaultTier) === 'Top' ? 'selected' : '' }}>최상위큐({{ $topRankScore }}+)</option>
            </select>
        </div>
        {{-- Removed static display of character/weapon from here --}}
    </div>
    <h3>기본정보</h3>

    <div class="table-wrapper">
    <table id="gameTable">
        <thead>
        <tr>
            <th>이름</th>
            <th>티어</th>
            <th>픽률</th>
            <th>
                평균획득점수<span class="info-icon" data-tooltip="입장료를 차감하지 않고 게임 내에서 획득한 점수를 나타냅니다.">ⓘ</span>
            </th>
            <th>승률</th>
            <th>TOP2</th>
            <th>TOP4</th>
            <th>막금구승률</th>
            <th>평균 TK</th>
            <th>이득확률</th>
            <th>이득평균점수</th>
            <th>손실확률</th>
            <th>손실평균점수</th>
        </tr>
        </thead>
        <tbody>
        <tr style="cursor: pointer;">
            <td class="character-cell">
                @php
                    // Format character ID to 3 digits with leading zeros
                    $formattedCharacterId = str_pad($byMain->character_id, 3, '0', STR_PAD_LEFT);
                    $characterIconPath = asset('storage/Character/icon/' . $formattedCharacterId . '.png');
                    $defaultCharacterIconPath = asset('storage/Character/icon/default.png');
                    $weaponIconPath = asset('storage/Weapon/' . $byMain->weapon_type_en . '.png');
                    $defaultWeaponIconPath = asset('storage/Weapon/icon/default.png');
                @endphp
                <div class="icon-container">
                    <img src="{{ $characterIconPath }}"
                         alt="{{ $byMain->character_name }}"
                         class="character-icon"
                         onerror="this.onerror=null; this.src='{{ $defaultCharacterIconPath }}';">
                    @if($byMain->weapon_type !== 'All')
                        <img src="{{ $weaponIconPath }}"
                             alt="{{ $byMain->weapon_type }}"
                             class="weapon-icon"
                             onerror="this.onerror=null; this.src='{{ $defaultWeaponIconPath }}';">
                    @endif
                </div>
                {{-- Display name and weapon on separate lines --}}
                <div class="character-name-weapon">
                    {{ $byMain->character_name }}<br>
                    <small>{{ $byMain->weapon_type }}</small> {{-- Smaller text for weapon type --}}
                </div>
            </td>
            <td data-score="{{ $byMain->meta_score }}">
                @php
                    $tier = $byMain->meta_tier;
                    $tierClass = 'tier-' . strtolower(str_replace(' ', '-', $tier)); // e.g., tier-op, tier-1, tier-rip
                @endphp
                <span class="tier-badge {{ $tierClass }}">{{ $tier }}</span>
                <div class="sub-stat">{{ number_format($byMain->meta_score_rank) }} / {{ number_format($byMainCount) }}</div>
            </td>
            <td class="number">
                <div>{{ number_format($byMain->game_count_percent , 2) }}%</div>
                <div class="sub-stat">{{ number_format($byMain->game_count_rank) }} / {{ number_format($byMainCount) }}</div>
            </td>
            <td class="number">
                {{ number_format($byMain->avg_mmr_gain, 1) }}
                <div class="sub-stat">{{ number_format($byMain->avg_mmr_gain_rank) }} / {{ number_format($byMainCount) }}</div>
            </td>
            <td class="number">
                <div>{{ number_format($byMain->top1_count_percent , 2) }}%</div>
                <div class="sub-stat">{{ number_format($byMain->top1_count_percent_rank) }} / {{ number_format($byMainCount) }}</div>
            </td>
            <td class="number">
                <div>{{ number_format($byMain->top2_count_percent , 2) }}%</div>
                <div class="sub-stat">{{ number_format($byMain->top2_count_percent_rank) }} / {{ number_format($byMainCount) }}</div>
            </td>
            <td class="number">
                <div>{{ number_format($byMain->top4_count_percent , 2) }}%</div>
                <div class="sub-stat">{{ number_format($byMain->top4_count_percent_rank) }} / {{ number_format($byMainCount) }}</div>
            </td>
            <td class="number">
                <div>{{ number_format($byMain->endgame_win_percent , 2) }}%</div>
                <div class="sub-stat">{{ number_format($byMain->endgame_win_percent_rank) }} / {{ number_format($byMainCount) }}</div>
            </td>
            <td class="number">
                {{ number_format($byMain->avg_team_kill_score, 2) }}
                <div class="sub-stat">{{ number_format($byMain->avg_team_kill_score_rank) }} / {{ number_format($byMainCount) }}</div>
            </td>
            <td class="number">
                <div>{{ number_format($byMain->positive_game_count_percent , 2) }}%</div>
                <div class="sub-stat">{{ number_format($byMain->positive_game_count_percent_rank) }} / {{ number_format($byMainCount) }}</div>
            </td>
            <td class="number">
                {{ number_format($byMain->positive_avg_mmr_gain, 1) }}
                <div class="sub-stat">{{ number_format($byMain->positive_avg_mmr_gain_rank) }} / {{ number_format($byMainCount) }}</div>
            </td>
            <td class="number">
                <div>{{ number_format($byMain->negative_game_count_percent , 2) }}%</div>
                <div class="sub-stat">{{ number_format($byMain->negative_game_count_percent_rank) }} / {{ number_format($byMainCount) }}</div>
            </td>
            <td class="number">
                {{ number_format($byMain->negative_avg_mmr_gain, 1) }}
                <div class="sub-stat">{{ number_format($byMain->negative_avg_mmr_gain_rank) }} / {{ number_format($byMainCount) }}</div>
            </td>
        </tr>
        </tbody>
    </table>
    </div>
    <h3>전체 티어정보 <button id="toggle-tier-info" class="toggle-button">▼ 펼치기</button></h3>

    <div id="tier-info-container" style="display: none;" data-lazy-section="tiers">
        <div class="loading-placeholder" style="padding: 20px; text-align: center;">
            <p>로딩 중...</p>
        </div>
    </div>

    <h3>순위 통계</h3>
    <div data-lazy-section="ranks">
        <div class="loading-placeholder" style="padding: 20px; text-align: center;">
            <p>로딩 중...</p>
        </div>
    </div>

    <h3>전술스킬 통계</h3>
    <div data-lazy-section="tacticalSkills">
        <div class="loading-placeholder" style="padding: 20px; text-align: center;">
            <p>로딩 중...</p>
        </div>
    </div>

    <h3>특성 통계</h3>
    <div data-lazy-section="traitStats">
        <div class="loading-placeholder" style="padding: 20px; text-align: center;">
            <p>로딩 중...</p>
        </div>
    </div>

    <!-- Equipment Section with Tabs -->
    <h3>아이템 통계</h3>
    <div data-lazy-section="equipment">
        <div class="loading-placeholder" style="padding: 20px; text-align: center;">
            <p>로딩 중...</p>
        </div>
    </div>
</div>

@push('scripts')
    <script src="{{ asset('js/detail.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/detail-lazy.js') }}?v={{ time() }}"></script>
@endpush
@endsection
