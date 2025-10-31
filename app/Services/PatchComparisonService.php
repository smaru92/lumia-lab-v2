<?php

namespace App\Services;

use App\Models\Character;
use App\Models\GameResultSummary;
use App\Models\PatchNote;
use App\Models\VersionHistory;
use App\Traits\ErDevTrait;

class PatchComparisonService
{
    use ErDevTrait;

    /**
     * 최신 버전 조회
     */
    public function getLatestVersion()
    {
        return VersionHistory::orderBy('version_season', 'desc')
            ->orderBy('version_major', 'desc')
            ->orderBy('version_minor', 'desc')
            ->first();
    }

    /**
     * 이전 버전 조회
     */
    public function getPreviousVersion($latestVersion)
    {
        return VersionHistory::where(function ($query) use ($latestVersion) {
            $query->where('version_season', '<', $latestVersion->version_season)
                ->orWhere(function ($q) use ($latestVersion) {
                    $q->where('version_season', '=', $latestVersion->version_season)
                        ->where('version_major', '<', $latestVersion->version_major);
                })
                ->orWhere(function ($q) use ($latestVersion) {
                    $q->where('version_season', '=', $latestVersion->version_season)
                        ->where('version_major', '=', $latestVersion->version_major)
                        ->where('version_minor', '<', $latestVersion->version_minor);
                });
        })
            ->orderBy('version_season', 'desc')
            ->orderBy('version_major', 'desc')
            ->orderBy('version_minor', 'desc')
            ->first();
    }

    /**
     * 패치노트 조회
     */
    public function getPatchNotes($versionHistoryId)
    {
        return PatchNote::where('version_history_id', $versionHistoryId)
            ->where('category', '캐릭터')
            ->whereNotNull('target_id')
            ->get();
    }

    /**
     * 캐릭터 정보 조회
     */
    public function getCharacters($characterIds)
    {
        return Character::whereIn('id', $characterIds)
            ->get()
            ->keyBy('id');
    }

    /**
     * 게임 결과 통계 조회
     */
    public function getGameResultStats($version, $minTier, $characterId, $weaponType = null)
    {
        $query = GameResultSummary::where('version_season', $version->version_season)
            ->where('version_major', $version->version_major)
            ->where('version_minor', $version->version_minor)
            ->where('min_tier', $minTier)
            ->where('character_id', $characterId);

        if ($weaponType) {
            $query->where('weapon_type', $weaponType);
            return $query->first();
        }

        return $query->get();
    }

    /**
     * 패치 비교 데이터 생성
     */
    public function comparePatches($latestVersion, $previousVersion, $patchNotes, $characters, $minTier)
    {
        $buffedCharacters = collect();
        $nerfedCharacters = collect();
        $processedCombinations = [];

        foreach ($patchNotes as $patchNote) {
            $characterId = $patchNote->target_id;
            $weaponType = $patchNote->weapon_type;
            $patchType = $patchNote->patch_type;

            $character = $characters[$characterId] ?? null;
            if (!$character) {
                continue;
            }

            // weapon_type이 없으면 해당 캐릭터의 모든 weapon_type 통계 조회
            if (empty($weaponType)) {
                $latestStatsCollection = $this->getGameResultStats($latestVersion, $minTier, $characterId);
                $previousStatsCollection = $this->getGameResultStats($previousVersion, $minTier, $characterId);

                // 각 weapon_type별로 처리
                foreach ($latestStatsCollection as $latestStat) {
                    $previousStat = $previousStatsCollection->where('weapon_type', $latestStat->weapon_type)->first();

                    if (!$previousStat) {
                        continue;
                    }

                    // 중복 체크
                    $combinationKey = $characterId . '_' . $latestStat->weapon_type;
                    if (isset($processedCombinations[$combinationKey])) {
                        continue;
                    }
                    $processedCombinations[$combinationKey] = true;

                    $comparison = $this->createComparison($character, $latestStat, $previousStat, $patchNote);

                    // patch_type 기준으로 버프/너프 판단
                    if ($this->isBuffPatch($patchType)) {
                        $buffedCharacters->push($comparison);
                    } elseif ($this->isNerfPatch($patchType)) {
                        $nerfedCharacters->push($comparison);
                    }
                }
            } else {
                // 중복 체크
                $combinationKey = $characterId . '_' . $weaponType;
                if (isset($processedCombinations[$combinationKey])) {
                    continue;
                }
                $processedCombinations[$combinationKey] = true;

                // 특정 weapon_type 통계 조회
                $latestStat = $this->getGameResultStats($latestVersion, $minTier, $characterId, $weaponType);
                $previousStat = $this->getGameResultStats($previousVersion, $minTier, $characterId, $weaponType);

                if (!$latestStat || !$previousStat) {
                    continue;
                }

                $comparison = $this->createComparison($character, $latestStat, $previousStat, $patchNote);

                // patch_type 기준으로 버프/너프 판단
                if ($this->isBuffPatch($patchType)) {
                    $buffedCharacters->push($comparison);
                } elseif ($this->isNerfPatch($patchType)) {
                    $nerfedCharacters->push($comparison);
                }
            }
        }

        return [
            'buffed' => $buffedCharacters->sortByDesc('meta_score_diff'),
            'nerfed' => $nerfedCharacters->sortBy('meta_score_diff'),
        ];
    }

    /**
     * 비교 데이터 생성
     */
    private function createComparison($character, $latestStat, $previousStat, $patchNote)
    {
        // weapon_type을 한글로 변환
        $weaponTypeEn = $latestStat->weapon_type_en ?? $latestStat->weapon_type;
        $weaponTypeKo = $this->replaceWeaponType($weaponTypeEn, 'ko');

        return [
            'character_id' => $character->id,
            'character_name' => $character->name,
            'weapon_type' => $weaponTypeKo,
            'weapon_type_en' => $weaponTypeEn,
            'patch_content' => $patchNote->content,
            'meta_score_diff' => $latestStat->meta_score - $previousStat->meta_score,
            'win_rate_diff' => $latestStat->top1_count_percent - $previousStat->top1_count_percent,
            'pick_rate_diff' => $latestStat->game_count_percent - $previousStat->game_count_percent,
            'avg_mmr_gain_diff' => $latestStat->avg_mmr_gain - $previousStat->avg_mmr_gain,
            'top4_rate_diff' => $latestStat->top4_count_percent - $previousStat->top4_count_percent,
            'latest' => $latestStat,
            'previous' => $previousStat,
        ];
    }

    /**
     * 버프 패치 여부 확인
     */
    private function isBuffPatch($patchType)
    {
        return strtolower($patchType) === '버프' || strtolower($patchType) === 'buff';
    }

    /**
     * 너프 패치 여부 확인
     */
    private function isNerfPatch($patchType)
    {
        return strtolower($patchType) === '너프' || strtolower($patchType) === 'nerf';
    }
}
