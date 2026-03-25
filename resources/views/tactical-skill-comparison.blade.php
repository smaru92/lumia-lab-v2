@extends('layouts.app')

@section('title', '전술스킬 버전별 비교 | 아글라이아 연구소')

@push('styles')
<style>
    .container {
        max-width: 1400px;
        margin-top: 70px;
    }

    /* 필터 영역 */
    .comparison-filter-container {
        display: flex;
        justify-content: center;
        align-items: flex-end;
        gap: 15px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .filter-group label {
        margin-bottom: 5px;
        font-weight: bold;
        font-size: 14px;
    }

    .filter-group select {
        padding: 8px 12px;
        font-size: 14px;
        border: 1px solid #ccc;
        border-radius: 5px;
        min-width: 160px;
        cursor: pointer;
    }

    .compare-btn {
        padding: 8px 24px;
        background: #2c3e50;
        color: #fff;
        border: none;
        border-radius: 5px;
        font-size: 14px;
        font-weight: bold;
        cursor: pointer;
        transition: background 0.2s;
        height: 38px;
    }

    .compare-btn:hover {
        background: #1a252f;
    }

    .version-arrow {
        font-size: 20px;
        color: #666;
        align-self: flex-end;
        padding-bottom: 6px;
    }

    /* 범례 */
    .legend {
        display: flex;
        gap: 20px;
        justify-content: center;
        margin-bottom: 15px;
        font-size: 13px;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .legend-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
    }

    .legend-dot.up { background: #e74c3c; }
    .legend-dot.down { background: #3498db; }

    /* 테이블 */
    .comparison-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    .comparison-table thead {
        position: sticky;
        z-index: 100;
    }

    .comparison-table th {
        background: #f4f4f4;
        border: 1px solid #ddd;
        padding: 8px 6px;
        text-align: center;
        font-size: 12px;
        white-space: nowrap;
        cursor: pointer;
    }

    .comparison-table th:hover {
        background: #e8e8e8;
    }

    .comparison-table td {
        border: 1px solid #ddd;
        padding: 6px 8px;
        text-align: center;
        white-space: nowrap;
    }

    .comparison-table tbody tr:nth-child(even) {
        background: #fafafa;
    }

    .comparison-table tbody tr:hover {
        background: #f0f7ff;
    }

    .skill-name-cell {
        font-weight: bold;
        text-align: left !important;
        min-width: 100px;
    }

    /* 헤더 그룹 색상 */
    .header-version-a {
        background: #3498db !important;
        color: #fff !important;
    }

    .header-version-b {
        background: #2ecc71 !important;
        color: #fff !important;
    }

    .header-diff {
        background: #e67e22 !important;
        color: #fff !important;
    }

    /* 버전A 컬럼 배경 */
    .col-version-a {
        background: rgba(52, 152, 219, 0.04);
    }

    /* 버전B 컬럼 배경 */
    .col-version-b {
        background: rgba(46, 204, 113, 0.04);
    }

    /* 변동 컬럼 배경 */
    .col-diff {
        background: rgba(230, 126, 34, 0.06);
    }

    /* 변동값 색상 */
    .diff-up {
        color: #e74c3c;
        font-weight: bold;
    }

    .diff-down {
        color: #3498db;
        font-weight: bold;
    }

    .diff-zero {
        color: #aaa;
    }

    .no-data {
        color: #ccc;
        font-style: italic;
    }

    /* 보기 모드 토글 */
    .view-mode-toggle {
        display: flex;
        justify-content: center;
        gap: 0;
        margin-bottom: 15px;
    }

    .toggle-btn {
        padding: 7px 20px;
        border: 1px solid #ccc;
        background: #fff;
        color: #666;
        font-size: 13px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.2s;
    }

    .toggle-btn:first-child {
        border-radius: 5px 0 0 5px;
    }

    .toggle-btn:last-child {
        border-radius: 0 5px 5px 0;
        border-left: none;
    }

    .toggle-btn.active {
        background: #2c3e50;
        color: #fff;
        border-color: #2c3e50;
    }

    .toggle-btn:hover:not(.active) {
        background: #f0f0f0;
    }

    /* 구분선 */
    .section-border-left {
        border-left: 2px solid #999 !important;
    }

    /* 반응형 - 모바일 */
    @media screen and (max-width: 599px) {
        .comparison-filter-container {
            gap: 10px;
        }

        .filter-group select {
            min-width: 120px;
            font-size: 13px;
        }

        .comparison-table {
            font-size: 11px;
        }

        .comparison-table th,
        .comparison-table td {
            padding: 4px 3px;
        }

        .skill-name-cell {
            min-width: 70px;
            font-size: 11px;
        }

        .hide-on-mobile {
            display: none;
        }
    }

    /* 반응형 - 태블릿 */
    @media screen and (min-width: 600px) and (max-width: 1024px) {
        .hide-on-tablet {
            display: none;
        }

        .comparison-table {
            font-size: 12px;
        }
    }
</style>
@endpush

@section('content')
<div class="container">
    <h2><a href="/tactical-skill-comparison">전술스킬 버전별 비교</a></h2>

    {{-- 필터: 버전 2개 선택 + 티어 + 비교 버튼 --}}
    <form method="GET" action="/tactical-skill-comparison" id="compareForm">
        <div class="comparison-filter-container">
            {{-- 이전 버전 (A) --}}
            <div class="filter-group">
                <label>이전 버전</label>
                <select name="version_a" id="selectVersionA">
                    @foreach($versionOptions as $opt)
                        <option value="{{ $opt['value'] }}" {{ $versionA === $opt['value'] ? 'selected' : '' }}>
                            {{ $opt['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <span class="version-arrow">→</span>

            {{-- 최신 버전 (B) --}}
            <div class="filter-group">
                <label>최신 버전</label>
                <select name="version_b" id="selectVersionB">
                    @foreach($versionOptions as $opt)
                        <option value="{{ $opt['value'] }}" {{ $versionB === $opt['value'] ? 'selected' : '' }}>
                            {{ $opt['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- 티어 --}}
            <div class="filter-group">
                <label>최소 티어</label>
                @php
                    $currentTier = $minTier;
                    $tierOptions = [
                        'All' => '전체',
                        'Platinum' => '플래티넘',
                        'Diamond' => '다이아',
                        'Diamond2' => '다이아2',
                        'Meteorite' => '메테오라이트',
                        'Mithrillow' => '미스릴',
                        'Mithrilhigh' => '미스릴(8000+)',
                        'Top' => '최상위큐(8000+)',
                    ];
                @endphp
                <select name="min_tier" id="selectTier">
                    @foreach($tierOptions as $value => $label)
                        <option value="{{ $value }}" {{ $currentTier === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- 보기 모드 --}}
            <input type="hidden" name="view_mode" id="inputViewMode" value="{{ $viewMode }}">

            {{-- 비교 버튼 --}}
            <button type="submit" class="compare-btn">비교하기</button>
        </div>
    </form>

    {{-- 합산 / 레벨별 토글 --}}
    <div class="view-mode-toggle">
        <button type="button" class="toggle-btn {{ $viewMode === 'combined' ? 'active' : '' }}" data-mode="combined">합산</button>
        <button type="button" class="toggle-btn {{ $viewMode === 'by_level' ? 'active' : '' }}" data-mode="by_level">레벨별</button>
    </div>

    @if(empty($comparisonData))
        <div style="text-align: center; padding: 50px; color: #999;">
            비교할 버전을 선택하고 "비교하기" 버튼을 눌러주세요.
        </div>
    @else
        {{-- 범례 --}}
        <div class="legend">
            <div class="legend-item">
                <span class="legend-dot up"></span>
                <span>상승</span>
            </div>
            <div class="legend-item">
                <span class="legend-dot down"></span>
                <span>하락</span>
            </div>
        </div>

        {{-- 비교 테이블 --}}
        <div class="table-wrapper" style="overflow-x: auto;">
            <table class="comparison-table" id="comparisonTable">
                <thead>
                    <tr>
                        <th rowspan="2">#</th>
                        <th rowspan="2">전술스킬</th>
                        <th colspan="7" class="header-version-a section-border-left">v{{ $versionA }} (이전)</th>
                        <th colspan="7" class="header-version-b section-border-left">v{{ $versionB }} (최신)</th>
                        <th colspan="5" class="header-diff section-border-left">변동</th>
                    </tr>
                    <tr>
                        {{-- 이전 버전 컬럼 --}}
                        <th class="section-border-left">픽률</th>
                        <th>승률</th>
                        <th class="hide-on-mobile">TOP2</th>
                        <th class="hide-on-mobile hide-on-tablet">TOP4</th>
                        <th>평균획득</th>
                        <th class="hide-on-mobile">이득확률</th>
                        <th class="hide-on-mobile hide-on-tablet">막금구</th>
                        {{-- 최신 버전 컬럼 --}}
                        <th class="section-border-left">픽률</th>
                        <th>승률</th>
                        <th class="hide-on-mobile">TOP2</th>
                        <th class="hide-on-mobile hide-on-tablet">TOP4</th>
                        <th>평균획득</th>
                        <th class="hide-on-mobile">이득확률</th>
                        <th class="hide-on-mobile hide-on-tablet">막금구</th>
                        {{-- 변동 컬럼 --}}
                        <th class="section-border-left">픽률</th>
                        <th>승률</th>
                        <th>평균획득</th>
                        <th class="hide-on-mobile">이득확률</th>
                        <th class="hide-on-mobile hide-on-tablet">막금구</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($comparisonData as $index => $row)
                        @php
                            $a = $row['version_a'];
                            $b = $row['version_b'];
                            $diff = $row['diff'];
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="skill-name-cell">{{ $row['tactical_skill_name'] }}</td>

                            {{-- 이전 버전 (A) 데이터 --}}
                            @if($a)
                                <td class="col-version-a section-border-left">{{ number_format($a->pick_rate, 2) }}%</td>
                                <td class="col-version-a">{{ number_format($a->top1_percent, 2) }}%</td>
                                <td class="col-version-a hide-on-mobile">{{ number_format($a->top2_percent, 2) }}%</td>
                                <td class="col-version-a hide-on-mobile hide-on-tablet">{{ number_format($a->top4_percent, 2) }}%</td>
                                <td class="col-version-a">{{ number_format($a->avg_mmr_gain, 1) }}</td>
                                <td class="col-version-a hide-on-mobile">{{ number_format($a->positive_percent, 2) }}%</td>
                                <td class="col-version-a hide-on-mobile hide-on-tablet">{{ number_format($a->endgame_win_percent ?? 0, 2) }}%</td>
                            @else
                                @for($i = 0; $i < 7; $i++)
                                    <td class="col-version-a no-data {{ $i === 0 ? 'section-border-left' : '' }} {{ in_array($i, [2,5]) ? 'hide-on-mobile' : '' }} {{ in_array($i, [3,6]) ? 'hide-on-mobile hide-on-tablet' : '' }}">-</td>
                                @endfor
                            @endif

                            {{-- 최신 버전 (B) 데이터 --}}
                            @if($b)
                                <td class="col-version-b section-border-left">{{ number_format($b->pick_rate, 2) }}%</td>
                                <td class="col-version-b">{{ number_format($b->top1_percent, 2) }}%</td>
                                <td class="col-version-b hide-on-mobile">{{ number_format($b->top2_percent, 2) }}%</td>
                                <td class="col-version-b hide-on-mobile hide-on-tablet">{{ number_format($b->top4_percent, 2) }}%</td>
                                <td class="col-version-b">{{ number_format($b->avg_mmr_gain, 1) }}</td>
                                <td class="col-version-b hide-on-mobile">{{ number_format($b->positive_percent, 2) }}%</td>
                                <td class="col-version-b hide-on-mobile hide-on-tablet">{{ number_format($b->endgame_win_percent ?? 0, 2) }}%</td>
                            @else
                                @for($i = 0; $i < 7; $i++)
                                    <td class="col-version-b no-data {{ $i === 0 ? 'section-border-left' : '' }} {{ in_array($i, [2,5]) ? 'hide-on-mobile' : '' }} {{ in_array($i, [3,6]) ? 'hide-on-mobile hide-on-tablet' : '' }}">-</td>
                                @endfor
                            @endif

                            {{-- 변동 --}}
                            @if($diff)
                                @php
                                    $diffFields = [
                                        ['key' => 'pick_rate', 'format' => 2, 'hide' => ''],
                                        ['key' => 'top1_percent', 'format' => 2, 'hide' => ''],
                                        ['key' => 'avg_mmr_gain', 'format' => 1, 'hide' => ''],
                                        ['key' => 'positive_percent', 'format' => 2, 'hide' => 'hide-on-mobile'],
                                        ['key' => 'endgame_win_percent', 'format' => 2, 'hide' => 'hide-on-mobile hide-on-tablet'],
                                    ];
                                @endphp
                                @foreach($diffFields as $fi => $field)
                                    @php
                                        $val = $diff[$field['key']];
                                        $cls = $val > 0 ? 'diff-up' : ($val < 0 ? 'diff-down' : 'diff-zero');
                                        $prefix = $val > 0 ? '+' : '';
                                    @endphp
                                    <td class="col-diff {{ $cls }} {{ $fi === 0 ? 'section-border-left' : '' }} {{ $field['hide'] }}">
                                        {{ $prefix }}{{ number_format($val, $field['format']) }}
                                    </td>
                                @endforeach
                            @else
                                <td class="col-diff no-data section-border-left">-</td>
                                <td class="col-diff no-data">-</td>
                                <td class="col-diff no-data">-</td>
                                <td class="col-diff no-data hide-on-mobile">-</td>
                                <td class="col-diff no-data hide-on-mobile hide-on-tablet">-</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function() {
    'use strict';

    // 보기 모드 토글
    var toggleBtns = document.querySelectorAll('.toggle-btn');
    toggleBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var mode = this.dataset.mode;
            document.getElementById('inputViewMode').value = mode;
            document.getElementById('compareForm').submit();
        });
    });

    // 테이블 정렬 기능
    const table = document.getElementById('comparisonTable');
    if (!table) return;

    const headerRow = table.querySelector('thead tr:last-child');
    const headers = headerRow.querySelectorAll('th');
    let currentSort = { column: -1, asc: false };

    headers.forEach(function(header, index) {
        header.addEventListener('click', function() {
            sortTable(index);
        });
    });

    function sortTable(colIndex) {
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        const asc = currentSort.column === colIndex ? !currentSort.asc : false;
        currentSort = { column: colIndex, asc: asc };

        rows.sort(function(a, b) {
            const aCell = a.cells[colIndex];
            const bCell = b.cells[colIndex];
            if (!aCell || !bCell) return 0;

            // 스킬명 (colIndex 1)
            if (colIndex === 1) {
                return asc
                    ? aCell.textContent.trim().localeCompare(bCell.textContent.trim())
                    : bCell.textContent.trim().localeCompare(aCell.textContent.trim());
            }

            var aVal = parseFloat(aCell.textContent.replace(/[%,+\s]/g, '')) || 0;
            var bVal = parseFloat(bCell.textContent.replace(/[%,+\s]/g, '')) || 0;
            return asc ? aVal - bVal : bVal - aVal;
        });

        rows.forEach(function(row, i) {
            row.cells[0].textContent = i + 1;
            tbody.appendChild(row);
        });
    }
})();
</script>
@endpush
