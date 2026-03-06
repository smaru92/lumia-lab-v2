<?php

namespace App\Services;

use App\Models\Character;
use App\Models\GameResultSummary;
use App\Models\PatchNote;
use App\Models\VersionHistory;
use App\Traits\ErDevTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PatchComparisonService
{
    use ErDevTrait;

    /**
     * 버전별 Summary 테이블명 가져오기
     */
    protected function getVersionedTableName($version): string
    {
        return VersionedGameTableManager::getTableName('game_results_summary', [
            'version_season' => $version->version_season,
            'version_major' => $version->version_major,
            'version_minor' => $version->version_minor,
        ]);
    }

    /**
     * 최신 버전 조회
     */
    public function getLatestVersion()
    {
        return VersionHistory::active()
            ->orderBy('version_season', 'desc')
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
        $tableName = $this->getVersionedTableName($version);

        // 테이블 존재 여부 확인
        if (!Schema::hasTable($tableName)) {
            return $weaponType ? null : collect();
        }

        $query = DB::table($tableName)
            ->where('min_tier', $minTier)
            ->where('character_id', $characterId);

        if ($weaponType) {
            $query->where('weapon_type', $weaponType);
            return $query->first();
        }

        return $query->get();
    }

    /**
     * 패치 비교 데이터 생성 (최적화 버전)
     */
    public function comparePatches($latestVersion, $previousVersion, $patchNotes, $characters, $minTier)
    {
        $buffedCharacters = collect();
        $nerfedCharacters = collect();

        // 1. 모든 캐릭터 ID 수집
        $characterIds = $patchNotes->pluck('target_id')->unique();

        // 2. 버전별 테이블명 가져오기
        $latestTableName = $this->getVersionedTableName($latestVersion);
        $previousTableName = $this->getVersionedTableName($previousVersion);

        // 3. 테이블 존재 여부 확인 - 없으면 빈 결과 반환
        if (!Schema::hasTable($latestTableName) || !Schema::hasTable($previousTableName)) {
            return [
                'buffed' => $buffedCharacters,
                'nerfed' => $nerfedCharacters,
            ];
        }

        // 4. 한 번에 모든 통계 조회 (N+1 해결!)
        $latestStats = DB::table($latestTableName)
            ->where('min_tier', $minTier)
            ->whereIn('character_id', $characterIds)
            ->get()
            ->groupBy('character_id');

        $previousStats = DB::table($previousTableName)
            ->where('min_tier', $minTier)
            ->whereIn('character_id', $characterIds)
            ->get()
            ->groupBy('character_id');

        // 5. 캐릭터+무기별 patch_type 수집
        $globalPatchTypes = []; // weapon_type이 없는 패치노트 (캐릭터 전체 적용)
        $specificPatchTypes = []; // weapon_type이 있는 패치노트

        foreach ($patchNotes as $patchNote) {
            $characterId = $patchNote->target_id;
            $weaponType = $patchNote->weapon_type;

            if (empty($weaponType)) {
                $globalPatchTypes[$characterId][] = $patchNote->patch_type;
            } else {
                $weaponTypeEn = $this->replaceWeaponType($weaponType, 'en');
                $key = $characterId . '_' . $weaponTypeEn;
                $specificPatchTypes[$key][] = $patchNote->patch_type;
            }
        }

        // 6. 패치노트 처리 (캐릭터+무기 조합별로 한 번만)
        $processedCombinations = [];

        foreach ($patchNotes as $patchNote) {
            $characterId = $patchNote->target_id;
            $weaponType = $patchNote->weapon_type;

            $character = $characters[$characterId] ?? null;
            if (!$character) {
                continue;
            }

            $latestStatsForChar = $latestStats[$characterId] ?? collect();
            $previousStatsForChar = $previousStats[$characterId] ?? collect();

            if ($latestStatsForChar->isEmpty() || $previousStatsForChar->isEmpty()) {
                continue;
            }

            // weapon_type이 없으면 해당 캐릭터의 모든 weapon_type 통계 처리
            if (empty($weaponType)) {
                foreach ($latestStatsForChar as $latestStat) {
                    $previousStat = $previousStatsForChar->where('weapon_type', $latestStat->weapon_type)->first();

                    if (!$previousStat) {
                        continue;
                    }

                    $combinationKey = $characterId . '_' . $latestStat->weapon_type;
                    if (isset($processedCombinations[$combinationKey])) {
                        continue;
                    }
                    $processedCombinations[$combinationKey] = true;

                    // 해당 조합의 모든 patch_type 수집 (전체 적용 + 무기별)
                    $allTypes = array_unique(array_merge(
                        $globalPatchTypes[$characterId] ?? [],
                        $specificPatchTypes[$combinationKey] ?? []
                    ));

                    $comparison = $this->createComparison($character, $latestStat, $previousStat, $patchNote);
                    $this->classifyBuffNerf($allTypes, $comparison, $buffedCharacters, $nerfedCharacters);
                }
            } else {
                $weaponTypeEn = $this->replaceWeaponType($weaponType, 'en');
                $combinationKey = $characterId . '_' . $weaponTypeEn;
                if (isset($processedCombinations[$combinationKey])) {
                    continue;
                }
                $processedCombinations[$combinationKey] = true;

                $latestStat = $latestStatsForChar->where('weapon_type', $weaponTypeEn)->first();
                $previousStat = $previousStatsForChar->where('weapon_type', $weaponTypeEn)->first();

                if (!$latestStat || !$previousStat) {
                    continue;
                }

                // 해당 조합의 모든 patch_type 수집 (전체 적용 + 무기별)
                $allTypes = array_unique(array_merge(
                    $globalPatchTypes[$characterId] ?? [],
                    $specificPatchTypes[$combinationKey] ?? []
                ));

                $comparison = $this->createComparison($character, $latestStat, $previousStat, $patchNote);
                $this->classifyBuffNerf($allTypes, $comparison, $buffedCharacters, $nerfedCharacters);
            }
        }

        return [
            'buffed' => $buffedCharacters->sortByDesc('meta_score_diff')->values(),
            'nerfed' => $nerfedCharacters->sortBy('meta_score_diff')->values(),
        ];
    }

    /**
     * patch_type 조합에 따라 버프/너프 목록에 분류
     * - 조정+버프 → 버프 목록
     * - 조정+너프 → 너프 목록
     * - 버프+너프 → 양쪽 모두
     * - 조정만 → 추가하지 않음
     */
    private function classifyBuffNerf($patchTypes, $comparison, &$buffedCharacters, &$nerfedCharacters)
    {
        $hasBuff = collect($patchTypes)->contains(fn($t) => $this->isBuffPatch($t));
        $hasNerf = collect($patchTypes)->contains(fn($t) => $this->isNerfPatch($t));

        if ($hasBuff) {
            $buffedCharacters->push($comparison);
        }
        if ($hasNerf) {
            $nerfedCharacters->push($comparison);
        }
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
