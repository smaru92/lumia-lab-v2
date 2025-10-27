@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/detail.css') }}">
@endpush

@section('content')
<div class="container">
    <h2><a href="/main?min_tier={{ request('min_tier') }}&version={{ request('version') }}">게임 통계</a></h2>

    {{-- Keep filters for changing options --}}
    <div class="filter-container">
        <div>
            <strong>버전 변경:</strong>
            <select id="sel-version-filter"> {{-- Changed ID to avoid conflict --}}
                @foreach($versions as $version)
                    <option value="{{ $version }}" {{ request('version', $defaultVersion) === $version ? 'selected' : '' }}>{{ $version }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <strong>최소 티어:</strong>
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

    <table id="gameTable">
        <thead>
        <tr>
            <th>이름</th>
            <th>티어</th>
            <th>픽률</th>
            <th>승률</th>
            <th>TOP2</th>
            <th>TOP4</th>
            <th>막금구승률</th>
            <th>
                평균획득점수
                <span class="info-icon" data-tooltip="입장료를 차감하지 않고 게임 내에서 획득 점수를 나타냅니다.">ⓘ</span>
            </th>
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
            <td>
                <div>{{ number_format($byMain->game_count_percent , 2) }}%</div>
                <div class="sub-stat">{{ number_format($byMain->game_count_rank) }} / {{ number_format($byMainCount) }}</div>
            </td>
            <td>
                <div>{{ number_format($byMain->top1_count_percent , 2) }}%</div>
                <div class="sub-stat">{{ number_format($byMain->top1_count_percent_rank) }} / {{ number_format($byMainCount) }}</div>
            </td>
            <td>
                <div>{{ number_format($byMain->top2_count_percent , 2) }}%</div>
                <div class="sub-stat">{{ number_format($byMain->top2_count_percent_rank) }} / {{ number_format($byMainCount) }}</div>
            </td>
            <td>
                <div>{{ number_format($byMain->top4_count_percent , 2) }}%</div>
                <div class="sub-stat">{{ number_format($byMain->top4_count_percent_rank) }} / {{ number_format($byMainCount) }}</div>
            </td>
            <td>
                <div>{{ number_format($byMain->endgame_win_percent , 2) }}%</div>
                <div class="sub-stat">{{ number_format($byMain->endgame_win_percent_rank) }} / {{ number_format($byMainCount) }}</div>
            </td>
            <td class="number">
                {{ number_format($byMain->avg_mmr_gain, 1) }}
                <div class="sub-stat">{{ number_format($byMain->avg_mmr_gain_rank) }} / {{ number_format($byMainCount) }}</div>
            </td>
            <td>
                <div>{{ number_format($byMain->positive_game_count_percent , 2) }}%</div>
                <div class="sub-stat">{{ number_format($byMain->positive_game_count_percent_rank) }} / {{ number_format($byMainCount) }}</div>
            </td>
            <td class="number">
                {{ number_format($byMain->positive_avg_mmr_gain, 1) }}
                <div class="sub-stat">{{ number_format($byMain->positive_avg_mmr_gain_rank) }} / {{ number_format($byMainCount) }}</div>
            </td>
            <td>
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
    <h3>전체 티어정보 <button id="toggle-tier-info" class="toggle-button">▼ 펼치기</button></h3>

    <div id="tier-info-container" style="display: none;">
        <table id="tierInfoTable">
            <thead>
            <tr>
                <th>최소티어</th>
                <th>티어</th>
                <th>픽률</th>
                <th>승률</th>
                <th>TOP2</th>
                <th>TOP4</th>
                <th>막금구승률</th>
                <th>
                    평균획득점수
                    <span class="info-icon" data-tooltip="입장료를 차감하지 않고 게임 내에서 획득 점수를 나타냅니다.">ⓘ</span>
                </th>
                <th>이득확률</th>
                <th>이득평균점수</th>
                <th>손실확률</th>
                <th>손실평균점수</th>
            </tr>
            </thead>
            <tbody>
            @foreach($byAll as $item)
            <tr style="cursor: pointer;">
                <td>
                    {{ $item->tier_name }}
                </td>
                <td data-score="{{ $item->meta_score }}">
                    @php
                        $tier = $item->meta_tier;
                        $tierClass = 'tier-' . strtolower(str_replace(' ', '-', $tier)); // e.g., tier-op, tier-1, tier-rip
                    @endphp
                    <span class="tier-badge {{ $tierClass }}">{{ $tier }}</span>
                    <div class="sub-stat">{{ number_format($item->meta_score_rank) }} / {{ number_format($item->rank_count) }}</div>
                </td>
                <td>
                    <div>{{ number_format($item->game_count_percent , 2) }}%</div>
                    <div class="sub-stat">{{ number_format($item->game_count_rank) }} / {{ number_format($item->rank_count) }}</div>
                </td>
                <td>
                    <div>{{ number_format($item->top1_count_percent , 2) }}%</div>
                    <div class="sub-stat">{{ number_format($item->top1_count_percent_rank) }} / {{ number_format($item->rank_count) }}</div>
                </td>
                <td>
                    <div>{{ number_format($item->top2_count_percent , 2) }}%</div>
                    <div class="sub-stat">{{ number_format($item->top2_count_percent_rank) }} / {{ number_format($item->rank_count) }}</div>
                </td>
                <td>
                    <div>{{ number_format($item->top4_count_percent , 2) }}%</div>
                    <div class="sub-stat">{{ number_format($item->top4_count_percent_rank) }} / {{ number_format($item->rank_count) }}</div>
                </td>
                <td>
                    <div>{{ number_format($item->endgame_win_percent , 2) }}%</div>
                    <div class="sub-stat">{{ number_format($item->endgame_win_percent_rank) }} / {{ number_format($item->rank_count) }}</div>
                </td>
                <td class="number">
                    {{ number_format($item->avg_mmr_gain, 1) }}
                    <div class="sub-stat">{{ number_format($item->avg_mmr_gain_rank) }} / {{ number_format($item->rank_count) }}</div>
                </td>
                <td>
                    <div>{{ number_format($item->positive_game_count_percent , 2) }}%</div>
                    <div class="sub-stat">{{ number_format($item->positive_game_count_percent_rank) }} / {{ number_format($item->rank_count) }}</div>
                </td>
                <td class="number">
                    {{ number_format($item->positive_avg_mmr_gain, 1) }}
                    <div class="sub-stat">{{ number_format($item->positive_avg_mmr_gain_rank) }} / {{ number_format($item->rank_count) }}</div>
                </td>
                <td>
                    <div>{{ number_format($item->negative_game_count_percent , 2) }}%</div>
                    <div class="sub-stat">{{ number_format($item->negative_game_count_percent_rank) }} / {{ number_format($item->rank_count) }}</div>
                </td>
                <td class="number">
                    {{ number_format($item->negative_avg_mmr_gain, 1) }}
                    <div class="sub-stat">{{ number_format($item->negative_avg_mmr_gain_rank) }} / {{ number_format($item->rank_count) }}</div>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <h3>순위 통계</h3>
    <table class="sortable-table">
        <thead>
        <tr>
            <th data-sort-index="1" data-sort-type="number">게임순위</th>
            <th data-sort-index="2" data-sort-type="number">비율</th>
            <th data-sort-index="3" data-sort-type="number">
                평균획득점수
                <span class="info-icon" data-tooltip="입장료를 차감하지 않고 게임 내에서 획득 점수를 나타냅니다.">ⓘ</span>
            </th>
            <th data-sort-index="4" data-sort-type="number">이득확률</th>
            <th data-sort-index="5" data-sort-type="number">이득평균점수</th>
            <th data-sort-index="6" data-sort-type="number">손실확률</th>
            <th data-sort-index="7" data-sort-type="number">손실평균점수</th>
        </tr>
        </thead>
        <tbody>
        @foreach($byRank as $item)
            @php
                $characterName = $item->character_name . ' ' . $item->weapon_type;
            @endphp
            <tr>
                <td>
                    <div>{{ $item->game_rank }}</div>
                </td>
                <td>
                    <div>{{ number_format($item->game_rank_count_percent , 2) }}%</div>
                    <div class="sub-stat">{{ $item->game_rank_count }}</div>
                </td>
                <td>
                    <div>{{ number_format($item->avg_mmr_gain , 2) }}</div>
                </td>
                <td>
                    <div>{{ number_format($item->positive_count_percent , 2) }}%</div>
                    <div class="sub-stat">{{ $item->positive_count }}</div>
                </td>
                <td class="number">{{ number_format($item->positive_avg_mmr_gain, 1) }}</td>
                <td>
                    <div>{{ number_format($item->negative_count_percent , 2) }}%</div>
                    <div class="sub-stat">{{ $item->negative_count }}</div>
                </td>
                <td class="number">{{ number_format($item->negative_avg_mmr_gain, 1) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>


    <h3>전술스킬 통계</h3>
    <table class="sortable-table">
        <thead>
        <tr>
            <th class="sortable" data-sort-index="1" data-sort-type="text">이름</th>
            <th class="sortable" data-sort-index="0" data-sort-type="grade">레벨</th>
            <th class="sortable" data-sort-index="2" data-sort-type="number">사용수</th>
            <th class="sortable" data-sort-index="3" data-sort-type="number">1위율</th>
            <th class="sortable" data-sort-index="4" data-sort-type="number">2위율</th>
            <th class="sortable" data-sort-index="5" data-sort-type="number">3위율</th>
            <th class="sortable" data-sort-index="6" data-sort-type="number">4위율</th>
        </tr>
        </thead>
        <tbody id="tactical-skill-tbody">

        @foreach($byTacticalSkillData as $index => $byTacticalSkill)
            @foreach($byTacticalSkill as $item)
            @php
                $firstTacticalSkillItem = reset($item);

                if($firstTacticalSkillItem->game_rank > 4) {
                    continue;
                }
            @endphp
            <tr class="tactical-skill-row" data-tactical_skill-id="{{ $firstTacticalSkillItem->tactical_skill_id }}" style="{{ $loop->parent->index >= 5 ? 'display: none;' : '' }}">
                <td style="white-space: nowrap; overflow: visible; text-overflow: unset;">{{ $firstTacticalSkillItem->tactical_skill_name }}</td>
                <td style="width: 40px; max-width: 40px; text-align: center; padding: 8px 4px;">{{ $firstTacticalSkillItem->tactical_skill_level }}</td>
                <td>{{ $byTacticalSkillTotal[$firstTacticalSkillItem->tactical_skill_id][$firstTacticalSkillItem->tactical_skill_level] }}</td>
                <td>
                    <div class="tooltip-wrap">
                        {{ number_format($item[1]->game_rank_count_percent, 2) }}%
                        <span class="tooltip-text">
                            게임 수: {{ $item[1]->game_rank_count }}<br>
                            평균 점수: {{ $item[1]->positive_avg_mmr_gain }}
                        </span>
                    </div>
                </td>
                <td>
                    <div class="tooltip-wrap">
                        {{ number_format($item[2]->game_rank_count_percent, 2) }}%
                        <span class="tooltip-text">
                            게임 수: {{ $item[2]->game_rank_count }}<br>
                            평균 점수: {{ $item[2]->positive_avg_mmr_gain }}
                        </span>
                    </div>
                </td>
                <td>
                    <div class="tooltip-wrap">
                        {{ number_format($item[3]->game_rank_count_percent, 2) }}%
                        <span class="tooltip-text">
                            게임 수: {{ $item[3]->game_rank_count }}<br>
                            평균 점수: {{ $item[3]->positive_avg_mmr_gain }}
                        </span>
                    </div>
                </td>
                <td>
                    <div class="tooltip-wrap">
                        {{ number_format($item[4]->game_rank_count_percent, 2) }}%
                        <span class="tooltip-text">
                            게임 수: {{ $item[4]->game_rank_count }}<br>
                            평균 점수: {{ $item[4]->positive_avg_mmr_gain }}
                        </span>
                    </div>
                </td>
            </tr>
        @endforeach
        @endforeach
        </tbody>
    </table>
    <button id="show-more-tactical-skills" class="show-more-button">더보기</button>


    <h3>특성 통계</h3>
    {{-- Trait Category Filter --}}
    <div class="trait-is-main-filter-container" style="margin-bottom: 10px;">
        <strong>특성 구분 필터:</strong>
        <label style="margin-right: 10px;">
            <input type="checkbox" class="trait-is-main-filter-checkbox" value="1" checked> 메인
        </label>
        <label style="margin-right: 10px;">
            <input type="checkbox" class="trait-is-main-filter-checkbox" value="0" checked> 서브
        </label>
    </div>
    <div class="trait-category-filter-container" style="margin-bottom: 10px;">
        <strong>특성 분류 필터:</strong>
        @foreach($traitCategories as $category)
            <label style="margin-right: 10px;">
                <input type="checkbox" class="trait-category-filter-checkbox" value="{{ $category }}" checked> {{ $category }}
            </label>
        @endforeach
    </div>
    <table class="sortable-table">
        <thead>
        <tr>
            <th class="sortable" data-sort-index="0" data-sort-type="text">이름</th>
            <th class="sortable" data-sort-index="1" data-sort-type="text">분류</th>
            <th class="sortable" data-sort-index="2" data-sort-type="text">구분</th>
            <th class="sortable" data-sort-index="3" data-sort-type="number">사용수</th>
            <th class="sortable" data-sort-index="4" data-sort-type="number">1위율</th>
            <th class="sortable" data-sort-index="5" data-sort-type="number">2위율</th>
            <th class="sortable" data-sort-index="6" data-sort-type="number">3위율</th>
            <th class="sortable" data-sort-index="7" data-sort-type="number">4위율</th>
        </tr>
        </thead>
        <tbody id="trait-tbody">

        @foreach($byTraitData as $index => $item)
                @php
                    $firstTraitItem = reset($item);
                    if($firstTraitItem->game_rank > 4) {
                    continue;
                }
                $traitCategory = $firstTraitItem->trait_category; // Store category for data attribute
            @endphp
            {{-- Add data-category attribute --}}
            <tr class="trait-row" data-trait-id="{{ $firstTraitItem->trait_id }}" data-category="{{ $traitCategory }}" data-is_main="{{ $firstTraitItem->is_main }}" style="{{ $loop->index >= 10 ? 'display: none;' : '' }}">
                <td>{{ $firstTraitItem->trait_name }}</td>
                <td>{{ $traitCategory }}</td>
                <td>{{ $firstTraitItem->is_main ? '메인' : '서브' }}</td>
                    <td>{{ $byTraitTotal[$firstTraitItem->trait_id] }}</td>
                    <td>
                        <div class="tooltip-wrap">
                            {{ number_format($item[1]->game_rank_count_percent, 2) }}%
                            <span class="tooltip-text">
                            게임 수: {{ $item[1]->game_rank_count }}<br>
                            평균 점수: {{ $item[1]->positive_avg_mmr_gain }}
                        </span>
                        </div>
                    </td>
                    <td>
                        <div class="tooltip-wrap">
                            {{ number_format($item[2]->game_rank_count_percent, 2) }}%
                            <span class="tooltip-text">
                            게임 수: {{ $item[2]->game_rank_count }}<br>
                            평균 점수: {{ $item[2]->positive_avg_mmr_gain }}
                        </span>
                        </div>
                    </td>
                    <td>
                        <div class="tooltip-wrap">
                            {{ number_format($item[3]->game_rank_count_percent, 2) }}%
                            <span class="tooltip-text">
                            게임 수: {{ $item[3]->game_rank_count }}<br>
                            평균 점수: {{ $item[3]->positive_avg_mmr_gain }}
                        </span>
                        </div>
                    </td>
                    <td>
                        <div class="tooltip-wrap">
                            {{ number_format($item[4]->game_rank_count_percent, 2) }}%
                            <span class="tooltip-text">
                            게임 수: {{ $item[4]->game_rank_count }}<br>
                            평균 점수: {{ $item[4]->positive_avg_mmr_gain }}
                        </span>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <button id="show-more-traits" class="show-more-button">더보기</button>
    <!-- Tab links -->
    <div class="tabs">
        <button class="tab-link active" onclick="openTab(event, 'weapon-stats')">무기</button>
        <button class="tab-link" onclick="openTab(event, 'chest-stats')">옷</button>
        <button class="tab-link" onclick="openTab(event, 'head-stats')">머리</button>
        <button class="tab-link" onclick="openTab(event, 'arm-stats')">팔/장식</button>
        <button class="tab-link" onclick="openTab(event, 'leg-stats')">다리</button>
    </div>

    <!-- Tab content -->
    @php
        $grades = [
            'Mythic' => '초월',
            'Legend' => '전설',
            'Epic' => '영웅',
        ]; // 등급 목록 정의

        $gradeTranslation = [
            'Mythic' => '초월',
            'Legend' => '전설',
            'Epic' => '영웅',
            'Rare' => '희귀',
            'Uncommon' => '고급',
            'Common' => '일반',
        ];

        $itemTypeTranslation = [
            'Weapon' => '무기',
            'Chest' => '옷',
            'Head' => '머리',
            'Arm' => '팔/장식',
            'Leg' => '다리',
        ];
    @endphp
    @foreach($byEquipmentData as $key => $equipmentData)
    <div id="{{ strtolower($key) }}-stats" class="tab-content {{ $key === 'Weapon' ? 'active' : ''}}">
        <h3>{{ $itemTypeTranslation[$key] ?? $key }} 통계</h3>
        <div class="grade-filter-container" style="margin-bottom: 10px;"> {{-- Added margin for spacing --}}
            <strong>등급 필터:</strong>
            @foreach($grades as $gradeEn => $gradeKo)
                <label style="margin-right: 10px;"> {{-- Added margin for spacing --}}
                    <input type="checkbox" class="grade-filter-checkbox" value="{{ $gradeEn }}" data-tab-key="{{ strtolower($key) }}" checked> {{ $gradeKo }}
                </label>
            @endforeach
        </div>
        <table class="sortable-table">
            <thead>
            <tr>
                <th class="sortable" data-sort-index="0" data-sort-type="grade">등급</th>
                <th class="sortable" data-sort-index="1" data-sort-type="text">이름</th>
                <th class="sortable" data-sort-index="2" data-sort-type="number">사용수</th>
                <th class="sortable" data-sort-index="3" data-sort-type="number">1위율</th>
                <th class="sortable" data-sort-index="4" data-sort-type="number">2위율</th>
                <th class="sortable" data-sort-index="5" data-sort-type="number">3위율</th>
                <th class="sortable" data-sort-index="6" data-sort-type="number">4위율</th>
            </tr>
            </thead>
            <tbody>

            @php
                $preequipmentName = '';
            @endphp

            @foreach($equipmentData as $item)
            @php
                // Ensure $firstItem is defined *before* use
                $firstItem = reset($item);
                if (!$firstItem) continue; // Skip if item is empty
                $equipmentName = $firstItem->equipment_name;
                if($firstItem->game_rank > 4) {
                    continue;
                }
            @endphp
            {{-- Make sure data-grade uses the defined $firstItem --}}
            <tr data-equipment-id="{{ $firstItem->equipment_id }}" data-grade="{{ $firstItem->item_grade }}">
                <td>{{ $preequipmentName == $equipmentName ? '' : ($gradeTranslation[$firstItem->item_grade] ?? $firstItem->item_grade) }}</td>
                <td>
                    @php
                        $equipmentId = $firstItem->equipment_id;
                    @endphp
                    <div class="tooltip-wrap" style="display: flex; align-items: center;">
                        @if($preequipmentName != $equipmentName)
                            <img src="{{ asset('storage/Equipment/' . $equipmentId . '.png') }}" alt="Equipment Icon" class="equipment-icon">
                        @endif
                        {{ $preequipmentName == $equipmentName ? '' : $equipmentName }}
                        <span class="tooltip-text">
                            @foreach($firstItem->equipment_stats as $equipmentStat)
                                {{ $equipmentStat['text'] }} + {{ $equipmentStat['value'] }}<br>
                            @endforeach
                        </span>
                    </div>
                </td>
                <td>{{ $byEquipmentTotal[$firstItem->equipment_id] }}</td>
                <td>
                    <div class="tooltip-wrap">
                        {{ number_format($item[1]->game_rank_count_percent, 2) }}%
                        <span class="tooltip-text">
                            게임 수: {{ $item[1]->game_rank_count }}<br>
                            평균 점수: {{ $item[1]->positive_avg_mmr_gain }}
                        </span>
                    </div>
                </td>
                <td>
                    <div class="tooltip-wrap">
                        {{ number_format($item[2]->game_rank_count_percent, 2) }}%
                        <span class="tooltip-text">
                            게임 수: {{ $item[2]->game_rank_count }}<br>
                            평균 점수: {{ $item[2]->positive_avg_mmr_gain }}
                        </span>
                    </div>
                </td>
                <td>
                    <div class="tooltip-wrap">
                        {{ number_format($item[3]->game_rank_count_percent, 2) }}%
                        <span class="tooltip-text">
                            게임 수: {{ $item[3]->game_rank_count }}<br>
                            평균 점수: {{ $item[3]->positive_avg_mmr_gain }}
                        </span>
                    </div>
                </td>
                <td>
                    <div class="tooltip-wrap">
                        {{ number_format($item[4]->game_rank_count_percent, 2) }}%
                        <span class="tooltip-text">
                            게임 수: {{ $item[4]->game_rank_count }}<br>
                            평균 점수: {{ $item[4]->positive_avg_mmr_gain }}
                        </span>
                    </div>
                </td>
            </tr>
            @php
                $preequipmentName = $equipmentName;
            @endphp
            @endforeach
            </tbody>
        </table>
    </div>
    @endforeach
</div>

@push('scripts')
    <script src="{{ asset('js/detail.js') }}"></script>
@endpush
@endsection
