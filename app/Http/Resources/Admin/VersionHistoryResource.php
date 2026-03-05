<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VersionHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'version_season' => $this->version_season,
            'version_major' => $this->version_major,
            'version_minor' => $this->version_minor,
            'start_date' => $this->start_date?->format('Y-m-d\TH:i'),
            'end_date' => $this->end_date?->format('Y-m-d\TH:i'),
            'version' => $this->version,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
