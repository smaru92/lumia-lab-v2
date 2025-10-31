@extends('layouts.app')

@section('content')
<div class="container">
    <h2><a href="/">패치 노트 영향 분석</a></h2>

    @if($latestVersion && $previousVersion)
    <div style="margin-bottom: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;">
        <h3 style="margin: 0 0 10px 0;">버전 비교</h3>
        <p style="margin: 5px 0;">
            <strong>최신 버전:</strong> {{ $latestVersion->version_season }}.{{ $latestVersion->version_major }}.{{ $latestVersion->version_minor }}
            ({{ $latestVersion->start_date->format('Y-m-d') }})
        </p>
        <p style="margin: 5px 0;">
            <strong>비교 버전:</strong> {{ $previousVersion->version_season }}.{{ $previousVersion->version_major }}.{{ $previousVersion->version_minor }}
            ({{ $previousVersion->start_date->format('Y-m-d') }})
        </p>
        <p style="margin: 5px 0; color: #666;">
            <small>* 다이아몬드 티어 기준 통계입니다.</small>
        </p>
    </div>

    <!-- 버프된 캐릭터 섹션 -->
    <div style="margin-bottom: 30px;">
        <h3 style="color: #28a745; margin-bottom: 15px;">
            🔼 버프된 캐릭터 ({{ $buffedCharacters->count() }}개)
        </h3>

        @if($buffedCharacters->count() > 0)
        <table id="buffedTable" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color: #d4edda; border-bottom: 2px solid #28a745;">
                    <th style="padding: 10px; text-align: left;">캐릭터</th>
                    <th style="padding: 10px; text-align: center;">티어 변동</th>
                    <th style="padding: 10px; text-align: center;">메타 스코어</th>
                    <th style="padding: 10px; text-align: center;">픽률</th>
                    <th style="padding: 10px; text-align: center;">승률</th>
                    <th style="padding: 10px; text-align: center;">TOP2</th>
                    <th style="padding: 10px; text-align: center;">TOP4</th>
                    <th style="padding: 10px; text-align: center;">막금구승률</th>
                    <th style="padding: 10px; text-align: center;">평균 획득점수</th>
                </tr>
            </thead>
            <tbody>
                @foreach($buffedCharacters as $item)
                <tr style="border-bottom: 1px solid #ddd; background-color: {{ $loop->iteration % 2 == 0 ? '#f8f9fa' : 'white' }};">
                    <td style="padding: 10px;">
                        <div style="display: flex; align-items: center;">
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
                            <div class="character-name-weapon">
                                {{ $item['character_name'] }}<br>
                                @if($weaponType && $weaponType !== 'All')
                                <small>{{ $weaponType }}</small>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td style="padding: 10px; text-align: center;">
                        @php
                            $prevTier = $item['previous']->meta_tier;
                            $latestTier = $item['latest']->meta_tier;
                            $prevTierClass = 'tier-' . strtolower(str_replace(' ', '-', $prevTier));
                            $latestTierClass = 'tier-' . strtolower(str_replace(' ', '-', $latestTier));
                        @endphp
                        <div style="display: flex; align-items: center; justify-content: center; gap: 5px;">
                            <span class="tier-badge {{ $prevTierClass }}" style="font-size: 11px; padding: 2px 6px;">{{ $prevTier }}</span>
                            <span style="color: #28a745;">→</span>
                            <span class="tier-badge {{ $latestTierClass }}" style="font-size: 11px; padding: 2px 6px;">{{ $latestTier }}</span>
                        </div>
                    </td>
                    <td style="padding: 10px; text-align: center;">
                        <div>
                            <span style="color: {{ $item['meta_score_diff'] > 0 ? '#28a745' : ($item['meta_score_diff'] < 0 ? '#dc3545' : '#666') }}; font-weight: bold;">
                                {{ $item['meta_score_diff'] > 0 ? '+' : '' }}{{ number_format($item['meta_score_diff'], 2) }}
                            </span>
                        </div>
                        <div style="font-size: 12px; color: #666;">
                            {{ number_format($item['previous']->meta_score, 2) }} → {{ number_format($item['latest']->meta_score, 2) }}
                        </div>
                    </td>
                    <td style="padding: 10px; text-align: center;">
                        <div>
                            <span style="color: {{ $item['pick_rate_diff'] > 0 ? '#28a745' : ($item['pick_rate_diff'] < 0 ? '#dc3545' : '#666') }}; font-weight: bold;">
                                {{ $item['pick_rate_diff'] > 0 ? '+' : '' }}{{ number_format($item['pick_rate_diff'], 2) }}%
                            </span>
                        </div>
                        <div style="font-size: 12px; color: #666;">
                            {{ number_format($item['previous']->game_count_percent, 2) }}% → {{ number_format($item['latest']->game_count_percent, 2) }}%
                        </div>
                    </td>
                    <td style="padding: 10px; text-align: center;">
                        <div>
                            <span style="color: {{ $item['win_rate_diff'] > 0 ? '#28a745' : ($item['win_rate_diff'] < 0 ? '#dc3545' : '#666') }}; font-weight: bold;">
                                {{ $item['win_rate_diff'] > 0 ? '+' : '' }}{{ number_format($item['win_rate_diff'], 2) }}%
                            </span>
                        </div>
                        <div style="font-size: 12px; color: #666;">
                            {{ number_format($item['previous']->top1_count_percent, 2) }}% → {{ number_format($item['latest']->top1_count_percent, 2) }}%
                        </div>
                    </td>
                    <td style="padding: 10px; text-align: center;">
                        @php
                            $top2_diff = $item['latest']->top2_count_percent - $item['previous']->top2_count_percent;
                        @endphp
                        <div>
                            <span style="color: {{ $top2_diff > 0 ? '#28a745' : ($top2_diff < 0 ? '#dc3545' : '#666') }}; font-weight: bold;">
                                {{ $top2_diff > 0 ? '+' : '' }}{{ number_format($top2_diff, 2) }}%
                            </span>
                        </div>
                        <div style="font-size: 12px; color: #666;">
                            {{ number_format($item['previous']->top2_count_percent, 2) }}% → {{ number_format($item['latest']->top2_count_percent, 2) }}%
                        </div>
                    </td>
                    <td style="padding: 10px; text-align: center;">
                        <div>
                            <span style="color: {{ $item['top4_rate_diff'] > 0 ? '#28a745' : ($item['top4_rate_diff'] < 0 ? '#dc3545' : '#666') }}; font-weight: bold;">
                                {{ $item['top4_rate_diff'] > 0 ? '+' : '' }}{{ number_format($item['top4_rate_diff'], 2) }}%
                            </span>
                        </div>
                        <div style="font-size: 12px; color: #666;">
                            {{ number_format($item['previous']->top4_count_percent, 2) }}% → {{ number_format($item['latest']->top4_count_percent, 2) }}%
                        </div>
                    </td>
                    <td style="padding: 10px; text-align: center;">
                        @php
                            $endgame_diff = $item['latest']->endgame_win_percent - $item['previous']->endgame_win_percent;
                        @endphp
                        <div>
                            <span style="color: {{ $endgame_diff > 0 ? '#28a745' : ($endgame_diff < 0 ? '#dc3545' : '#666') }}; font-weight: bold;">
                                {{ $endgame_diff > 0 ? '+' : '' }}{{ number_format($endgame_diff, 2) }}%
                            </span>
                        </div>
                        <div style="font-size: 12px; color: #666;">
                            {{ number_format($item['previous']->endgame_win_percent, 2) }}% → {{ number_format($item['latest']->endgame_win_percent, 2) }}%
                        </div>
                    </td>
                    <td style="padding: 10px; text-align: center;">
                        <div>
                            <span style="color: {{ $item['avg_mmr_gain_diff'] > 0 ? '#28a745' : ($item['avg_mmr_gain_diff'] < 0 ? '#dc3545' : '#666') }}; font-weight: bold;">
                                {{ $item['avg_mmr_gain_diff'] > 0 ? '+' : '' }}{{ number_format($item['avg_mmr_gain_diff'], 1) }}
                            </span>
                        </div>
                        <div style="font-size: 12px; color: #666;">
                            {{ number_format($item['previous']->avg_mmr_gain, 1) }} → {{ number_format($item['latest']->avg_mmr_gain, 1) }}
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="color: #666; padding: 20px; text-align: center; background-color: #f8f9fa; border-radius: 5px;">
            버프된 캐릭터가 없습니다.
        </p>
        @endif
    </div>

    <!-- 너프된 캐릭터 섹션 -->
    <div style="margin-bottom: 30px;">
        <h3 style="color: #dc3545; margin-bottom: 15px;">
            🔽 너프된 캐릭터 ({{ $nerfedCharacters->count() }}개)
        </h3>

        @if($nerfedCharacters->count() > 0)
        <table id="nerfedTable" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color: #f8d7da; border-bottom: 2px solid #dc3545;">
                    <th style="padding: 10px; text-align: left;">캐릭터</th>
                    <th style="padding: 10px; text-align: center;">티어 변동</th>
                    <th style="padding: 10px; text-align: center;">메타 스코어</th>
                    <th style="padding: 10px; text-align: center;">픽률</th>
                    <th style="padding: 10px; text-align: center;">승률</th>
                    <th style="padding: 10px; text-align: center;">TOP2</th>
                    <th style="padding: 10px; text-align: center;">TOP4</th>
                    <th style="padding: 10px; text-align: center;">막금구승률</th>
                    <th style="padding: 10px; text-align: center;">평균 획득점수</th>
                </tr>
            </thead>
            <tbody>
                @foreach($nerfedCharacters as $item)
                <tr style="border-bottom: 1px solid #ddd; background-color: {{ $loop->iteration % 2 == 0 ? '#f8f9fa' : 'white' }};">
                    <td style="padding: 10px;">
                        <div style="display: flex; align-items: center;">
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
                            <div class="character-name-weapon">
                                {{ $item['character_name'] }}<br>
                                @if($weaponType && $weaponType !== 'All')
                                <small>{{ $weaponType }}</small>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td style="padding: 10px; text-align: center;">
                        @php
                            $prevTier = $item['previous']->meta_tier;
                            $latestTier = $item['latest']->meta_tier;
                            $prevTierClass = 'tier-' . strtolower(str_replace(' ', '-', $prevTier));
                            $latestTierClass = 'tier-' . strtolower(str_replace(' ', '-', $latestTier));
                        @endphp
                        <div style="display: flex; align-items: center; justify-content: center; gap: 5px;">
                            <span class="tier-badge {{ $prevTierClass }}" style="font-size: 11px; padding: 2px 6px;">{{ $prevTier }}</span>
                            <span style="color: #dc3545;">→</span>
                            <span class="tier-badge {{ $latestTierClass }}" style="font-size: 11px; padding: 2px 6px;">{{ $latestTier }}</span>
                        </div>
                    </td>
                    <td style="padding: 10px; text-align: center;">
                        <div>
                            <span style="color: {{ $item['meta_score_diff'] > 0 ? '#28a745' : ($item['meta_score_diff'] < 0 ? '#dc3545' : '#666') }}; font-weight: bold;">
                                {{ $item['meta_score_diff'] > 0 ? '+' : '' }}{{ number_format($item['meta_score_diff'], 2) }}
                            </span>
                        </div>
                        <div style="font-size: 12px; color: #666;">
                            {{ number_format($item['previous']->meta_score, 2) }} → {{ number_format($item['latest']->meta_score, 2) }}
                        </div>
                    </td>
                    <td style="padding: 10px; text-align: center;">
                        <div>
                            <span style="color: {{ $item['pick_rate_diff'] > 0 ? '#28a745' : ($item['pick_rate_diff'] < 0 ? '#dc3545' : '#666') }}; font-weight: bold;">
                                {{ $item['pick_rate_diff'] > 0 ? '+' : '' }}{{ number_format($item['pick_rate_diff'], 2) }}%
                            </span>
                        </div>
                        <div style="font-size: 12px; color: #666;">
                            {{ number_format($item['previous']->game_count_percent, 2) }}% → {{ number_format($item['latest']->game_count_percent, 2) }}%
                        </div>
                    </td>
                    <td style="padding: 10px; text-align: center;">
                        <div>
                            <span style="color: {{ $item['win_rate_diff'] > 0 ? '#28a745' : ($item['win_rate_diff'] < 0 ? '#dc3545' : '#666') }}; font-weight: bold;">
                                {{ $item['win_rate_diff'] > 0 ? '+' : '' }}{{ number_format($item['win_rate_diff'], 2) }}%
                            </span>
                        </div>
                        <div style="font-size: 12px; color: #666;">
                            {{ number_format($item['previous']->top1_count_percent, 2) }}% → {{ number_format($item['latest']->top1_count_percent, 2) }}%
                        </div>
                    </td>
                    <td style="padding: 10px; text-align: center;">
                        @php
                            $top2_diff = $item['latest']->top2_count_percent - $item['previous']->top2_count_percent;
                        @endphp
                        <div>
                            <span style="color: {{ $top2_diff > 0 ? '#28a745' : ($top2_diff < 0 ? '#dc3545' : '#666') }}; font-weight: bold;">
                                {{ $top2_diff > 0 ? '+' : '' }}{{ number_format($top2_diff, 2) }}%
                            </span>
                        </div>
                        <div style="font-size: 12px; color: #666;">
                            {{ number_format($item['previous']->top2_count_percent, 2) }}% → {{ number_format($item['latest']->top2_count_percent, 2) }}%
                        </div>
                    </td>
                    <td style="padding: 10px; text-align: center;">
                        <div>
                            <span style="color: {{ $item['top4_rate_diff'] > 0 ? '#28a745' : ($item['top4_rate_diff'] < 0 ? '#dc3545' : '#666') }}; font-weight: bold;">
                                {{ $item['top4_rate_diff'] > 0 ? '+' : '' }}{{ number_format($item['top4_rate_diff'], 2) }}%
                            </span>
                        </div>
                        <div style="font-size: 12px; color: #666;">
                            {{ number_format($item['previous']->top4_count_percent, 2) }}% → {{ number_format($item['latest']->top4_count_percent, 2) }}%
                        </div>
                    </td>
                    <td style="padding: 10px; text-align: center;">
                        @php
                            $endgame_diff = $item['latest']->endgame_win_percent - $item['previous']->endgame_win_percent;
                        @endphp
                        <div>
                            <span style="color: {{ $endgame_diff > 0 ? '#28a745' : ($endgame_diff < 0 ? '#dc3545' : '#666') }}; font-weight: bold;">
                                {{ $endgame_diff > 0 ? '+' : '' }}{{ number_format($endgame_diff, 2) }}%
                            </span>
                        </div>
                        <div style="font-size: 12px; color: #666;">
                            {{ number_format($item['previous']->endgame_win_percent, 2) }}% → {{ number_format($item['latest']->endgame_win_percent, 2) }}%
                        </div>
                    </td>
                    <td style="padding: 10px; text-align: center;">
                        <div>
                            <span style="color: {{ $item['avg_mmr_gain_diff'] > 0 ? '#28a745' : ($item['avg_mmr_gain_diff'] < 0 ? '#dc3545' : '#666') }}; font-weight: bold;">
                                {{ $item['avg_mmr_gain_diff'] > 0 ? '+' : '' }}{{ number_format($item['avg_mmr_gain_diff'], 1) }}
                            </span>
                        </div>
                        <div style="font-size: 12px; color: #666;">
                            {{ number_format($item['previous']->avg_mmr_gain, 1) }} → {{ number_format($item['latest']->avg_mmr_gain, 1) }}
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="color: #666; padding: 20px; text-align: center; background-color: #f8f9fa; border-radius: 5px;">
            너프된 캐릭터가 없습니다.
        </p>
        @endif
    </div>

    @else
    <div style="padding: 40px; text-align: center; background-color: #f8f9fa; border-radius: 5px;">
        <p style="color: #666; font-size: 16px;">비교할 버전 데이터가 없습니다.</p>
    </div>
    @endif
</div>
@endsection
