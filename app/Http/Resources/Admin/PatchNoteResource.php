<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatchNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'version_history_id' => $this->version_history_id,
            'category' => $this->category,
            'target_id' => $this->target_id,
            'weapon_type' => $this->weapon_type,
            'skill_type' => $this->skill_type,
            'patch_type' => $this->patch_type,
            'content' => $this->content,
            'target_name' => $this->target_name ?? null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
