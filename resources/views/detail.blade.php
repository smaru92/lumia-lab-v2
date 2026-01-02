@extends('layouts.app')

@section('title', '상세 통계 | 아글라이아 연구소')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/detail.css') }}?v={{ time() }}">
@endpush

@section('content')
<div class="container">
    <h2><a href="/character?min_tier={{ request('min_tier') }}&version={{ request('version') }}">게임 통계</a></h2>

    {{-- Keep filters for changing options --}}
    <div class="detail-filter-container main-filter-container">
        @include('partials.filter-dropdowns')
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
            <th>평균TK</th>
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
    @if(!empty($byRank))
    @php
        // 최대값을 기준으로 상대적 너비 계산
        $maxPercent = collect($byRank)->max('game_rank_count_percent');
    @endphp
    {{-- 순위 분포 차트 + 데이터 통합 --}}
    <div class="rank-chart-table">
        {{-- 헤더 --}}
        <div class="rank-row rank-header">
            <div class="rank-chart-area">
                <span class="rank-label">순위</span>
                <div class="rank-bar-wrap header-label">분포</div>
                <span class="rank-percent">비율</span>
            </div>
            <div class="rank-data-area">
                <div class="rank-data-item"><span class="header-text">획득</span></div>
                <div class="rank-data-item"><span class="header-text">평균TK</span></div>
                <div class="rank-data-item"><span class="header-text">이득확률</span></div>
                <div class="rank-data-item"><span class="header-text">이득평균</span></div>
                <div class="rank-data-item"><span class="header-text">손실확률</span></div>
                <div class="rank-data-item"><span class="header-text">손실평균</span></div>
            </div>
        </div>
        @foreach($byRank as $item)
        @php
            // 최대값 대비 상대적 너비 (최대값이 100%가 됨)
            $barWidth = $maxPercent > 0 ? ($item->game_rank_count_percent / $maxPercent) * 100 : 0;
            $barWidth = max($barWidth, 5); // 최소 5% 너비
            $scoreClass = $item->avg_mmr_gain >= 0 ? 'positive' : 'negative';
        @endphp
        <div class="rank-row" data-rank="{{ $item->game_rank }}">
            {{-- 차트 영역 --}}
            <div class="rank-chart-area">
                <span class="rank-label">{{ $item->game_rank }}위</span>
                <div class="rank-bar-wrap">
                    <div class="rank-bar" style="width: {{ $barWidth }}%;" data-rank="{{ $item->game_rank }}"></div>
                </div>
                <span class="rank-percent">{{ number_format($item->game_rank_count_percent, 1) }}%</span>
            </div>
            {{-- 데이터 영역 --}}
            <div class="rank-data-area">
                <div class="rank-data-item">
                    <span class="data-label">획득</span>
                    <span class="data-value {{ $scoreClass }}">{{ $item->avg_mmr_gain >= 0 ? '+' : '' }}{{ number_format($item->avg_mmr_gain, 2) }}</span>
                </div>
                <div class="rank-data-item">
                    <span class="data-label">평균TK</span>
                    <span class="data-value">{{ number_format($item->avg_team_kill_score, 2) }}</span>
                </div>
                <div class="rank-data-item">
                    <span class="data-label">이득확률</span>
                    <span class="data-value">{{ number_format($item->positive_count_percent, 1) }}%</span>
                </div>
                <div class="rank-data-item">
                    <span class="data-label">이득평균</span>
                    <span class="data-value positive">+{{ number_format($item->positive_avg_mmr_gain, 2) }}</span>
                </div>
                <div class="rank-data-item">
                    <span class="data-label">손실확률</span>
                    <span class="data-value">{{ number_format($item->negative_count_percent, 1) }}%</span>
                </div>
                <div class="rank-data-item">
                    <span class="data-label">손실평균</span>
                    <span class="data-value negative">{{ number_format($item->negative_avg_mmr_gain, 2) }}</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <p style="text-align: center; color: #999;">데이터가 없습니다.</p>
    @endif

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
