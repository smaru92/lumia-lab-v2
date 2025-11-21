@extends('layouts.app')

@section('title', '캐릭터 통계 | 아글라이아 연구소')

@section('content')
<div class="container">
    <h2><a href="/character">캐릭터 통계</a></h2>
    <div style="margin-bottom: 15px;">
    <!-- 중앙 정렬 컨테이너 -->
    <div class="main-filter-container">
        <div style="display: flex; flex-direction: column; align-items: center;">
            <label for="sel-min-tier" style="margin-bottom: 5px;"><strong>버전</strong></label>
            <select id="sel-min-tier">
                @foreach($versions as $version)
                    <option value="{{ $version }}" {{ request('version', $defaultVersion) === $version ? 'selected' : '' }}>{{ $version }}</option>
                @endforeach
            </select>
        </div>
        <div style="display: flex; flex-direction: column; align-items: center;">
            <label for="sel-version" style="margin-bottom: 5px;"><strong>최소 티어</strong></label>
            <select id="sel-version">
                <option value="All" {{ request('min_tier', $defaultTier) === 'All' ? 'selected' : '' }}>전체</option>
                <option value="Platinum" {{ request('min_tier', $defaultTier) === 'Platinum' ? 'selected' : '' }}>플레티넘</option>
                <option value="Diamond" {{ request('min_tier', $defaultTier) === 'Diamond' ? 'selected' : '' }}>다이아</option>
                <option value="Diamond2" {{ request('min_tier', $defaultTier) === 'Diamond2' ? 'selected' : '' }}>다이아2</option>
                <option value="Meteorite" {{ request('min_tier', $defaultTier) === 'Meteorite' ? 'selected' : '' }}>메테오라이트</option>
                <option value="Mithril" {{ request('min_tier', $defaultTier) === 'Mithril' ? 'selected' : '' }}>미스릴</option>
                <option value="Top" {{ request('min_tier', $defaultTier) === 'Top' ? 'selected' : '' }}>최상위큐({{ $topRankScore }}+)</option>
            </select>
        </div>
        <div style="display: flex; flex-direction: column; align-items: center;">
            <label for="input-pick-rate" style="margin-bottom: 5px;"><strong>최소 픽률(%)</strong></label>
            <input type="number" id="input-pick-rate" min="0" max="100" step="0.1" value="0.5" style="padding: 8px; font-size: 16px; border: 1px solid #ccc; border-radius: 5px; width: 100px;">
        </div>
    </div>

    <!-- 하단 컨테이너 -->
    <div class="bottom-container" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
        <!-- 좌측: 최근 업데이트 일자 -->
        <div class="update-info" style="font-size: 14px; color: #777; white-space: nowrap;">
            <strong>최근 업데이트:</strong> {{ $lastUpdate }}
        </div>
        <!-- 우측: 티어표 보기 버튼 -->
        <button id="openTierModal" class="tier-modal-btn" style="padding: 8px 15px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">티어표 보기</button>
    </div>
</div>

    <div class="table-wrapper">
    <table id="gameTable">
        <thead>
        <tr>
            <th>랭크</th> {{-- 정렬 기능 없음 --}}
            <th class="sortable">이름</th>
            <th class="sortable">티어</th>
            <th class="sortable">픽률</th>
            <th class="sortable">평균획득점수<span class="info-icon" data-tooltip="입장료를 차감하지 않고 게임 내에서 획득 점수를 나타냅니다.">ⓘ</span></th>
            <th class="sortable">승률</th>
            <th class="sortable hide-on-mobile">TOP2</th>
            <th class="sortable hide-on-mobile">TOP4</th>
            <th class="sortable hide-on-mobile hide-on-tablet">막금구승률</th>
            <th class="sortable hide-on-mobile">평균 TK</th>
            <th class="sortable hide-on-mobile">이득확률</th>
            <th class="sortable hide-on-mobile">손실확률</th>
        </tr>
        </thead>
        <tbody>
        @php
            $preCharacter = '';
        @endphp
        @foreach($data as $item)
                @php
                    $characterName = $item->character_name . ' ' . $item->weapon_type
                @endphp
                <tr style="cursor: pointer;" data-href="/detail/{{ $item->character_name }}-{{ $item->weapon_type }}?min_tier={{ request('min_tier', $defaultTier) }}&version={{ request('version', $defaultVersion) }}">
                    <td>{{ $loop->iteration }}</td> {{-- 랭크 번호 표시 --}}
                    <td class="character-cell">
                        @if($preCharacter != $characterName)
                            @php
                                // Format character ID to 3 digits with leading zeros
                                $formattedCharacterId = str_pad($item->character_id, 3, '0', STR_PAD_LEFT);
                                $characterIconPath = image_asset('storage/Character/icon/' . $formattedCharacterId . '.png');
                                $defaultCharacterIconPath = image_asset('storage/Character/icon/default.png');
                                $weaponIconPath = image_asset('storage/Weapon/' . $item->weapon_type_en . '.png');
                                $defaultWeaponIconPath = image_asset('storage/Weapon/icon/default.png');
                            @endphp
                            <div class="icon-container">
                                <img src="{{ $characterIconPath }}"
                                     alt="{{ $item->character_name }}"
                                     class="character-icon"
                                     loading="lazy"
                                     onerror="this.onerror=null; this.src='{{ $defaultCharacterIconPath }}';">
                                @if($item->weapon_type !== 'All')
                                <img src="{{ $weaponIconPath }}"
                                     alt="{{ $item->weapon_type }}"
                                     class="weapon-icon"
                                     loading="lazy"
                                     onerror="this.onerror=null; this.src='{{ $defaultWeaponIconPath }}';">
                                @endif
                            </div>
                            {{-- Display name and weapon on separate lines --}}
                            <div class="character-name-weapon">
                                {{ $item->character_name }}<br>
                                <small>{{ $item->weapon_type }}</small> {{-- Smaller text for weapon type --}}
                            </div>
                        @endif
                    </td>
                    <td data-score="{{ $item->meta_score }}">
                        @php
                            $tier = $item->meta_tier;
                            $tierClass = 'tier-' . strtolower(str_replace(' ', '-', $tier)); // e.g., tier-op, tier-1, tier-rip
                        @endphp
                        <span class="tier-badge {{ $tierClass }}">{{ $tier }}</span>
                        <div class="sub-stat">{{ number_format($item->meta_score, 2) }}</div>
                    </td>
                    <td class="number">
                        <div>{{ number_format($item->game_count_percent , 2) }}%</div>
                        <div class="sub-stat">{{ $item->game_count }}</div>
                    </td>
                    <td class="number">{{ number_format($item->avg_mmr_gain, 1) }}</td>
                    <td class="number">
                        <div>{{ number_format($item->top1_count_percent , 2) }}%</div>
                        <div  class="sub-stat">{{ $item->top1_count }}</div>
                    </td>
                    <td class="hide-on-mobile number">
                        <div>{{ number_format($item->top2_count_percent , 2) }}%</div>
                        <div class="sub-stat">{{ $item->top2_count }}</div>
                    </td>
                    <td class="hide-on-mobile number">
                        <div>{{ number_format($item->top4_count_percent , 2) }}%</div>
                        <div class="sub-stat">{{ $item->top4_count }}</div>
                    </td>
                    <td class="hide-on-mobile hide-on-tablet number">
                        <div>{{ number_format($item->endgame_win_percent , 2) }}%</div>
                    </td>
                    <td class="hide-on-mobile number">{{ number_format($item->avg_team_kill_score, 2) }}</td>
                    <td class="hide-on-mobile number">
                        <div>{{ number_format($item->positive_game_count_percent , 2) }}%</div>
                        <div class="sub-stat">평균 +{{ number_format($item->positive_avg_mmr_gain, 1) }}점</div>
                    </td>
                    <td class="hide-on-mobile number">
                        <div>{{ number_format($item->negative_game_count_percent , 2) }}%</div>
                        <div class="sub-stat">평균 {{ number_format($item->negative_avg_mmr_gain, 1) }}점</div>
                    </td>
                </tr>
                @php
                    $preCharacter = $characterName;
                @endphp
            @endforeach
        </tbody>
    </table>
    </div>
</div>

<!-- Tier Modal -->
<div id="tierModal" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h3>티어표</h3>
        <table class="tier-table">
            <tbody>
                @php
                    $tiers = ['OP', '1', '2', '3', '4', '5', 'RIP'];
                    $groupedByTier = $data->groupBy('meta_tier');
                @endphp
                @foreach($tiers as $tier)
                    @php
                        $hasItems = isset($groupedByTier[$tier]) && count($groupedByTier[$tier]) > 0;
                        $isSpecialTier = in_array($tier, ['OP', 'RIP']); // OP and RIP tiers should be hidden when empty
                    @endphp
                    @if($hasItems)
                        @php
                            $tierClass = 'tier-' . strtolower(str_replace(' ', '-', $tier)); // e.g., tier-op, tier-1, tier-rip
                        @endphp
                        <tr>
                            <td style="text-align: center; vertical-align: middle;"><span class="tier-badge {{ $tierClass }} ">{{ $tier }}</span></td>
                            <td>
                                @foreach($groupedByTier[$tier] as $item)
                                    <div class="tier-character-icon-container"
                                         data-pick-rate="{{ $item->game_count_percent }}">
                                    @php
                                        $formattedCharacterId = str_pad($item->character_id, 3, '0', STR_PAD_LEFT);
                                        $characterIconPath = image_asset('storage/Character/icon/' . $formattedCharacterId . '.png');
                                        $defaultCharacterIconPath = image_asset('storage/Character/icon/default.png');
                                        $weaponIconPath = image_asset('storage/Weapon/' . $item->weapon_type_en . '.png');
                                        $defaultWeaponIconPath = image_asset('storage/Weapon/icon/default.png');
                                    @endphp

                                    <img src="{{ $characterIconPath }}"
                                             alt="{{ $item->character_name }}"
                                             class="tier-character-icon"
                                             loading="lazy"
                                             onerror="this.onerror=null; this.src='{{ $defaultCharacterIconPath }}';">
                                    @if($item->weapon_type !== 'All')
                                    <img src="{{ $weaponIconPath }}"
                                             alt="{{ $item->weapon_type }}"
                                             class="tier-weapon-icon"
                                             loading="lazy"
                                             onerror="this.onerror=null; this.src='{{ $defaultWeaponIconPath }}';">
                                    @endif
                                    </div>
                                @endforeach
                            </td>
                        </tr>
                    @elseif(!$isSpecialTier)
                        {{-- Show empty tier row with proper height only for regular tiers (1,2,3,4,5) --}}
                        @php
                            $tierClass = 'tier-' . strtolower(str_replace(' ', '-', $tier)); // e.g., tier-1, tier-2, etc.
                        @endphp
                        <tr class="empty-tier-row">
                            <td style="text-align: center; vertical-align: middle;"><span class="tier-badge {{ $tierClass }}">{{ $tier }}</span></td>
                            <td class="empty-tier-content">&nbsp;</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
        <button class="modal-close-btn-bottom close-modal">닫기</button>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/main.js') }}?v={{ time() }}"></script>
@endpush
