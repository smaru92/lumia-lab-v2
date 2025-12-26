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
            $mithrilHighScore = 8000;
            $tierOptions = [
                'All' => ['name' => '전체', 'icon' => 'All'],
                'Platinum' => ['name' => '플래티넘', 'icon' => 'Platinum'],
                'Diamond' => ['name' => '다이아', 'icon' => 'Diamond'],
                'Diamond2' => ['name' => '다이아2', 'icon' => 'Diamond'],
                'Meteorite' => ['name' => '메테오라이트', 'icon' => 'Meteorite'],
                'Mithrillow' => ['name' => '미스릴', 'icon' => 'Mithril'],
                'Mithrilhigh' => ['name' => '미스릴(' . $mithrilHighScore . '+)', 'icon' => 'Mithril'],
                'Top' => ['name' => '최상위큐(' . ($topRankScore ?? '8000') . '+)', 'icon' => 'Demigod'],
            ];
            $selectedTier = $tierOptions[$currentTier] ?? $tierOptions['All'];
        @endphp
        <div class="dropdown-selected" data-value="{{ $currentTier }}">
            @if($selectedTier['icon'])
                <span class="tier-icon" style="background-image: url('{{ asset('storage/Tier/icon/' . $selectedTier['icon'] . '.png') }}');" aria-label="{{ $selectedTier['name'] }}"></span>
            @endif
            <span>{{ $selectedTier['name'] }}</span>
        </div>
        <div class="dropdown-options">
            @foreach($tierOptions as $value => $tier)
                <div class="dropdown-option {{ $currentTier === $value ? 'selected' : '' }}" data-value="{{ $value }}">
                    @if($tier['icon'])
                        <span class="tier-icon" style="background-image: url('{{ asset('storage/Tier/icon/' . $tier['icon'] . '.png') }}');" aria-label="{{ $tier['name'] }}"></span>
                    @endif
                    <span>{{ $tier['name'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>
