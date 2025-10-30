<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatchNote extends Model
{
    protected $table = 'patch_notes';

    protected $fillable = [
        'version_history_id',
        'category',
        'target_id',
        'weapon_type',
        'skill_type',
        'patch_type',
        'content',
    ];

    /**
     * 버전 히스토리 관계
     */
    public function versionHistory(): BelongsTo
    {
        return $this->belongsTo(VersionHistory::class);
    }

    /**
     * 캐릭터 관계
     */
    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'target_id');
    }

    /**
     * 장비 관계
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class, 'target_id');
    }

    /**
     * 특성 관계
     */
    public function gameTrait(): BelongsTo
    {
        return $this->belongsTo(\App\Models\GameTrait::class, 'target_id');
    }

    /**
     * 전술 스킬 관계
     */
    public function tacticalSkill(): BelongsTo
    {
        return $this->belongsTo(\App\Models\TacticalSkill::class, 'target_id');
    }

    /**
     * 대상 이름 반환
     */
    public function getTargetNameAttribute(): ?string
    {
        if (!$this->target_id) {
            return null;
        }

        return match ($this->category) {
            '캐릭터' => $this->character?->name,
            '아이템' => $this->equipment?->name,
            '특성' => $this->gameTrait?->name ?? null,
            '전술스킬' => $this->tacticalSkill?->name ?? null,
            default => null,
        };
    }
}
