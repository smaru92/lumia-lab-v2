@extends('layouts.app')

@section('title', '특성 통계 | 아글라이아 연구소')

@section('content')
<div class="container">
    <h2><a href="/trait">특성 통계</a></h2>
    <div style="margin-bottom: 15px;">
        <!-- 중앙 정렬 컨테이너 -->
        <div class="main-filter-container">
            @include('partials.filter-dropdowns')
            <div class="custom-dropdown-container">
                <label><strong>최소 사용수</strong></label>
                <input type="number" id="input-min-count" min="0" step="1" value="0" style="padding: 8px; font-size: 16px; border: 1px solid #ccc; border-radius: 5px; width: 100px;">
            </div>
            <div class="custom-dropdown-container">
                <label><strong>특성 타입</strong></label>
                <select id="sel-trait-type">
                    <option value="All" selected>전체</option>
                    <option value="main">메인 특성</option>
                    <option value="sub">서브 특성</option>
                </select>
            </div>
            <div class="custom-dropdown-container">
                <label><strong>특성 카테고리</strong></label>
                <select id="sel-trait-category">
                    <option value="All" selected>전체 카테고리</option>
                    <option value="파괴">파괴</option>
                    <option value="혼돈">혼돈</option>
                    <option value="지원">지원</option>
                    <option value="저항">저항</option>
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
        <!-- Beta 안내문구 -->
        <div class="beta-warning-box">
            <span class="warning-icon">⚠️</span>
            <p class="warning-text">이 페이지는 현재 실험 중인 기능입니다. 지표 평가가 부정확할 수 있으니 참고용으로만 활용해 주세요.</p>
        </div>
    </div>

    <div class="table-wrapper">
    <table id="gameTable">
        <thead>
        <tr>
            <th>랭크</th>
            <th class="sortable">이름</th>
            <th class="sortable">티어</th>
            <th class="sortable">사용수</th>
            <th class="sortable">평균획득점수<span class="info-icon" data-tooltip="입장료를 차감하지 않고 게임 내에서 획득한 점수를 나타냅니다.">ⓘ</span></th>
            <th class="sortable">승률</th>
            <th class="sortable hide-on-mobile">TOP2</th>
            <th class="sortable hide-on-mobile">TOP4</th>
            <th class="sortable hide-on-mobile hide-on-tablet">막금구승률</th>
            <th class="sortable hide-on-mobile">평균TK</th>
            <th class="sortable">이득확률</th>
            <th class="sortable hide-on-mobile">손실확률</th>
        </tr>
        </thead>
        <tbody>
        @foreach($data as $item)
            @php
                $traitIconPath = asset('storage/Trait/' . $item->trait_id . '.png');
                $defaultTraitIconPath = asset('storage/Trait/default.png');
            @endphp
            <tr style="cursor: pointer;"
                data-trait-type="{{ $item->is_main ? 'main' : 'sub' }}"
                data-trait-category="{{ $item->trait_category }}">
                <td>{{ $loop->iteration }}</td>
                <td class="trait-cell">
                    <div class="icon-container tooltip-wrap">
                        <img src="{{ $traitIconPath }}"
                             alt="{{ $item->trait_name }}"
                             class="trait-icon {{ $item->is_main ? 'trait-main' : 'trait-sub' }}"
                             onerror="this.onerror=null; this.src='{{ $defaultTraitIconPath }}';">
                        <span class="tooltip-text">
                            <strong>{{ $item->trait_name }}</strong><br>
                            {{ $item->trait_tooltip ?? '특성 정보 없음' }}
                        </span>
                    </div>
                    <div class="trait-name-info">
                        {{ $item->trait_name }}<br>
                        <small>{{ $item->is_main ? '메인' : '서브' }} / {{ $item->trait_category_ko }}</small>
                    </div>
                </td>
                <td data-score="{{ $item->meta_score }}">
                    @php
                        $tier = $item->meta_tier;
                        $tierClass = 'tier-' . strtolower(str_replace(' ', '-', $tier));
                    @endphp
                    <span class="tier-badge {{ $tierClass }}">{{ $tier }}</span>
                    <div class="sub-stat">{{ number_format($item->meta_score, 2) }}</div>
                </td>
                <td class="number">
                    {{ number_format($item->game_count) }}
                </td>
                <td class="number">{{ number_format($item->avg_mmr_gain, 1) }}</td>
                <td class="number">
                    <div>{{ number_format($item->top1_count_percent, 2) }}%</div>
                    <div class="sub-stat">{{ $item->top1_count }}</div>
                </td>
                <td class="hide-on-mobile number">
                    <div>{{ number_format($item->top2_count_percent, 2) }}%</div>
                    <div class="sub-stat">{{ $item->top2_count }}</div>
                </td>
                <td class="hide-on-mobile number">
                    <div>{{ number_format($item->top4_count_percent, 2) }}%</div>
                    <div class="sub-stat">{{ $item->top4_count }}</div>
                </td>
                <td class="hide-on-mobile hide-on-tablet number">
                    <div>{{ number_format($item->endgame_win_percent, 2) }}%</div>
                </td>
                <td class="hide-on-mobile number">{{ number_format($item->avg_team_kill_score, 2) }}</td>
                <td class="hide-on-mobile number">
                    <div>{{ number_format($item->positive_game_count_percent, 2) }}%</div>
                    <div class="sub-stat">평균 +{{ number_format($item->positive_avg_mmr_gain, 1) }}점</div>
                </td>
                <td class="hide-on-mobile number">
                    <div>{{ number_format($item->negative_game_count_percent, 2) }}%</div>
                    <div class="sub-stat">평균 {{ number_format($item->negative_avg_mmr_gain, 1) }}점</div>
                </td>
            </tr>
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
                        $isSpecialTier = in_array($tier, ['OP', 'RIP']);
                    @endphp
                    @if($hasItems)
                        @php
                            $tierClass = 'tier-' . strtolower(str_replace(' ', '-', $tier));
                        @endphp
                        <tr>
                            <td style="text-align: center; vertical-align: middle;"><span class="tier-badge {{ $tierClass }}">{{ $tier }}</span></td>
                            <td>
                                @foreach($groupedByTier[$tier] as $item)
                                    <div class="tier-character-icon-container"
                                         data-game-count="{{ $item->game_count }}"
                                         data-trait-type="{{ $item->is_main ? 'main' : 'sub' }}"
                                         data-trait-category="{{ $item->trait_category }}"
                                         data-trait-name="{{ $item->trait_name }}"
                                         data-tier="{{ $item->meta_tier }}"
                                         data-win-rate="{{ number_format($item->top1_count_percent, 2) }}"
                                         data-top2-rate="{{ number_format($item->top2_count_percent, 2) }}"
                                         data-top4-rate="{{ number_format($item->top4_count_percent, 2) }}"
                                         data-avg-score="{{ number_format($item->avg_mmr_gain, 1) }}">
                                    @php
                                        $traitIconPath = asset('storage/Trait/' . $item->trait_id . '.png');
                                        $defaultTraitIconPath = asset('storage/Trait/default.png');
                                    @endphp
                                    <img src="{{ $traitIconPath }}"
                                         alt="{{ $item->trait_name }}"
                                         class="tier-character-icon {{ $item->is_main ? 'trait-main-border' : '' }}"
                                         onerror="this.onerror=null; this.src='{{ $defaultTraitIconPath }}';">
                                    </div>
                                @endforeach
                            </td>
                        </tr>
                    @elseif(!$isSpecialTier)
                        @php
                            $tierClass = 'tier-' . strtolower(str_replace(' ', '-', $tier));
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
    <script src="{{ asset('js/trait.js') }}?v={{ time() }}"></script>
@endpush
