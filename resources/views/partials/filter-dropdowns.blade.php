{{-- 버전 드롭다운 --}}
<div class="custom-dropdown-container">
    <label><strong>버전</strong></label>
    <div class="custom-dropdown" id="version-dropdown">
        <div class="dropdown-selected" data-value="{{ request('version', $defaultVersion) }}">
            {{ request('version', $defaultVersion) }}
        </div>
        <div class="dropdown-options">
            @foreach($versions as $version)
                <div class="dropdown-option {{ request('version', $defaultVersion) === $version ? 'selected' : '' }}" data-value="{{ $version }}">
                    {{ $version }}
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- 최소 티어 드롭다운 --}}
<div class="custom-dropdown-container">
    <label><strong>최소 티어</strong></label>
    <div class="custom-dropdown" id="tier-dropdown">
        @php
            $currentTier = request('min_tier', $defaultTier);
            $tierOptions = [
                'All' => ['name' => '전체', 'icon' => null],
                'Platinum' => ['name' => '플래티넘', 'icon' => 'Platinum'],
                'Diamond' => ['name' => '다이아', 'icon' => 'Diamond'],
                'Diamond2' => ['name' => '다이아2', 'icon' => 'Diamond'],
                'Meteorite' => ['name' => '메테오라이트', 'icon' => 'Meteorite'],
                'Mithril' => ['name' => '미스릴', 'icon' => 'Mithril'],
                'Top' => ['name' => '최상위큐(' . ($topRankScore ?? '8000') . '+)', 'icon' => 'Demigod'],
            ];
            $selectedTier = $tierOptions[$currentTier] ?? $tierOptions['All'];
        @endphp
        <div class="dropdown-selected" data-value="{{ $currentTier }}">
            @if($selectedTier['icon'])
                <img src="{{ asset('storage/Tier/' . $selectedTier['icon'] . '.png') }}" alt="{{ $selectedTier['name'] }}" class="tier-icon" onerror="this.style.display='none'">
            @endif
            <span>{{ $selectedTier['name'] }}</span>
        </div>
        <div class="dropdown-options">
            @foreach($tierOptions as $value => $tier)
                <div class="dropdown-option {{ $currentTier === $value ? 'selected' : '' }}" data-value="{{ $value }}">
                    @if($tier['icon'])
                        <img src="{{ asset('storage/Tier/' . $tier['icon'] . '.png') }}" alt="{{ $tier['name'] }}" class="tier-icon" onerror="this.style.display='none'">
                    @endif
                    <span>{{ $tier['name'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>
