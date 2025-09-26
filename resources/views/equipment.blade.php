@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
@endpush

@section('content')
<div class="container">
    <h2><a href="/equipment">장비 통계</a></h2>
    <div style="margin-bottom: 15px;">
        <!-- 중앙 정렬 컨테이너 -->
        <div style="display: flex; justify-content: center; align-items: center; width: 100%; margin-bottom: 10px;">
            <div class="filter-container">
                <label for="sel-min-tier"><strong>버전:</strong> </label>
                <select id="sel-min-tier">
                    @foreach($versions as $version)
                        <option value="{{ $version }}" {{ request('version', $defaultVersion) === $version ? 'selected' : '' }}>{{ $version }}</option>
                    @endforeach
                </select>
                <label for="sel-version"><strong>최소 티어 선택:</strong> </label>
                <select id="sel-version">
                    <option value="All" {{ request('min_tier', $defaultTier) === 'All' ? 'selected' : '' }}>전체</option>
                    <option value="Platinum" {{ request('min_tier', $defaultTier) === 'Platinum' ? 'selected' : '' }}>플레티넘</option>
                    <option value="Diamond" {{ request('min_tier', $defaultTier) === 'Diamond' ? 'selected' : '' }}>다이아</option>
                    <option value="Diamond2" {{ request('min_tier', $defaultTier) === 'Diamond2' ? 'selected' : '' }}>다이아2</option>
                    <option value="Meteorite" {{ request('min_tier', $defaultTier) === 'Meteorite' ? 'selected' : '' }}>메테오라이트</option>
                    <option value="Mithril" {{ request('min_tier', $defaultTier) === 'Mithril' ? 'selected' : '' }}>미스릴</option>
                    <option value="Top" {{ request('min_tier', $defaultTier) === 'Top' ? 'selected' : '' }}>최상위큐({{ $topRankScore }}+)</option>
                </select>
                <label for="input-pick-rate" style="margin-left: 10px;"><strong>최소 픽률(%):</strong> </label>
                <input type="number" id="input-pick-rate" min="0" max="100" step="0.01" style="padding: 8px; font-size: 16px; border: 1px solid #ccc; border-radius: 5px; width: 80px;">
            </div>
        </div>
        <div style="display: flex; justify-content: center; align-items: center; width: 100%; margin-bottom: 10px;">
            <div class="filter-container">
                <label for="sel-item-grade"><strong>아이템 등급:</strong> </label>
                <select id="sel-item-grade">
                    <option value="All" selected>전체 등급</option>
                    <option value="Legend">전설</option>
                    <option value="Mythic">초월</option>
                </select>
                <label for="sel-item-type2" style="margin-left: 10px;"><strong>아이템 부위:</strong> </label>
                <select id="sel-item-type2">
                    <option value="All" selected>전체 부위</option>
                    <option value="Chest">옷</option>
                    <option value="Head">머리</option>
                    <option value="Arm">팔</option>
                    <option value="Leg">다리</option>
                </select>
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

    <table id="gameTable">
        <thead>
        <tr>
            <th>랭크</th> {{-- 정렬 기능 없음 --}}
            <th class="sortable">이름</th>
            <th class="sortable">티어</th>
            <th class="sortable">픽률</th>
            <th class="sortable">승률</th>
            <th class="sortable">TOP2</th>
            <th class="sortable">TOP4</th>
            <th class="sortable">막금구승률</th>
            <th class="sortable">이득확률</th>
            <th class="sortable">손실확률</th>
            <th class="sortable">평균획득점수</th>
            <th class="sortable">이득평균점수</th>
            <th class="sortable">손실평균점수</th>
        </tr>
        </thead>
        <tbody>
        @php
            $preEquipmentName = '';
        @endphp
        @foreach($data as $item)
                @php
                    $equipmentName = $item->equipment_name;
                    // item_grade_en과 item_type2를 data attribute로 추가
                @endphp
                <tr style="cursor: pointer;" data-item-grade="{{ $item->item_grade_en }}" data-item-type2="{{ $item->item_type2_en }}">
                    <td>{{ $loop->iteration }}</td> {{-- 랭크 번호 표시 --}}
                    <td class="equipment-cell">
                        @if($preEquipmentName != $equipmentName)
                            @php
                                // Format equipment ID to 3 digits with leading zeros
                                $formattedEquipmentId = $item->equipment_id;
                                $equipmentIconPath = asset('storage/Equipment/' . $formattedEquipmentId . '.png');
                                $defaultEquipmentIconPath = asset('storage/Equipment/default.png');
                                // $weaponIconPath = asset('storage/Weapon/' . $item->weapon_type_en . '.png'); // 장비 페이지에서는 불필요
                                // $defaultWeaponIconPath = asset('storage/Weapon/icon/default.png');
                            @endphp
                            <div class="icon-container">
                                <img src="{{ $equipmentIconPath }}"
                                     alt="{{ $item->equipment_name }}"
                                     class="equipment-icon"
                                     onerror="this.onerror=null; this.src='{{ $defaultEquipmentIconPath }}';">
                            </div>
                            {{-- Display name and weapon on separate lines --}}
                            <div class="equipment-name-weapon">
                                {{ $item->equipment_name }}<br>
                                <small>{{ $item->item_grade }} {{ $item->item_type2 }}</small> {{-- 등급과 부위 함께 표시 --}}
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
                    <td>
                        <div>{{ number_format($item->game_count_percent , 2) }}%</div>
                        <div class="sub-stat">{{ $item->game_count }}</div>
                    </td>
                    <td>
                        <div>{{ number_format($item->top1_count_percent , 2) }}%</div>
                        <div  class="sub-stat">{{ $item->top1_count }}</div>
                    </td>
                    <td>
                        <div>{{ number_format($item->top2_count_percent , 2) }}%</div>
                        <div class="sub-stat">{{ $item->top2_count }}</div>
                    </td>
                    <td>
                        <div>{{ number_format($item->top4_count_percent , 2) }}%</div>
                        <div class="sub-stat">{{ $item->top4_count }}</div>
                    </td>
                    <td>
                        <div>{{ number_format($item->endgame_win_percent , 2) }}%</div>
                    </td>
                    <td>
                        <div>{{ number_format($item->positive_game_count_percent , 2) }}%</div>
                        <div class="sub-stat">{{ $item->positive_game_count }}</div>
                    </td>
                    <td>
                        <div>{{ number_format($item->negative_game_count_percent , 2) }}%</div>
                        <div class="sub-stat">{{ $item->negative_game_count }}</div>
                    </td>
                    <td class="number">{{ number_format($item->avg_mmr_gain, 1) }}</td>
                    <td class="number">{{ number_format($item->positive_avg_mmr_gain, 1) }}</td>
                    <td class="number">{{ number_format($item->negative_avg_mmr_gain, 1) }}</td>
                </tr>
                @php
                    $preEquipmentName = $equipmentName;
                @endphp
            @endforeach
        </tbody>
    </table>
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
                    @if(isset($groupedByTier[$tier]) && count($groupedByTier[$tier]) > 0)
                        @php
                            $tierClass = 'tier-' . strtolower(str_replace(' ', '-', $tier)); // e.g., tier-op, tier-1, tier-rip
                        @endphp
                        <tr>
                            <td style="text-align: center; vertical-align: middle;"><span class="tier-badge {{ $tierClass }} ">{{ $tier }}</span></td>
                            <td>
                                @foreach($groupedByTier[$tier] as $item)
                                    <div class="tier-character-icon-container"
                                         data-pick-rate="{{ number_format($item->game_count_percent, 2) }}"
                                         data-item-grade="{{ $item->item_grade_en }}"
                                         data-item-type2="{{ $item->item_type2_en }}"
                                         data-equipment-name="{{ $item->equipment_name }}"
                                         data-tier="{{ $item->meta_tier }}"
                                         data-win-rate="{{ number_format($item->top1_count_percent, 2) }}"
                                         data-top2-rate="{{ number_format($item->top2_count_percent, 2) }}"
                                         data-top4-rate="{{ number_format($item->top4_count_percent, 2) }}"
                                         data-avg-score="{{ number_format($item->avg_mmr_gain, 1) }}">
                                    @php
                                        $formattedEquipmentId = $item->equipment_id;
                                        $equipmentIconPath = asset('storage/Equipment/' . $formattedEquipmentId . '.png');
                                        $defaultEquipmentIconPath = asset('storage/Equipment/default.png');
                                    @endphp
                                    <img src="{{ $equipmentIconPath }}"
                                             alt="{{ $item->equipment_name }}"
                                             class="tier-character-icon"
                                             onerror="this.onerror=null; this.src='{{ $defaultEquipmentIconPath }}';">
                                    </div>
                                @endforeach
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/main.js') }}"></script>
@endpush

