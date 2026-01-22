<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\PatchNoteResource;
use App\Models\Character;
use App\Models\Equipment;
use App\Models\GameTrait;
use App\Models\PatchNote;
use App\Models\TacticalSkill;
use App\Models\VersionHistory;
use Illuminate\Http\Request;

class PatchNoteController extends Controller
{
    public function index(VersionHistory $versionHistory)
    {
        $patchNotes = $versionHistory->patchNotes()->orderBy('id', 'desc')->get();

        $patchNotes = $patchNotes->map(function ($note) {
            $note->target_name = $this->getTargetName($note);
            return $note;
        });

        return PatchNoteResource::collection($patchNotes);
    }

    public function store(Request $request, VersionHistory $versionHistory)
    {
        $validated = $request->validate([
            'category' => 'required|string|in:캐릭터,특성,아이템,시스템,전술스킬,기타',
            'patch_type' => 'required|string|in:버프,너프,조정,리워크,신규,삭제',
            'target_id' => 'nullable|integer',
            'weapon_type' => 'nullable|string',
            'skill_type' => 'nullable|string',
            'content' => 'required|string',
        ]);

        $validated['version_history_id'] = $versionHistory->id;

        $patchNote = PatchNote::create($validated);
        $patchNote->target_name = $this->getTargetName($patchNote);

        return new PatchNoteResource($patchNote);
    }

    public function update(Request $request, PatchNote $patchNote)
    {
        $validated = $request->validate([
            'category' => 'required|string|in:캐릭터,특성,아이템,시스템,전술스킬,기타',
            'patch_type' => 'required|string|in:버프,너프,조정,리워크,신규,삭제',
            'target_id' => 'nullable|integer',
            'weapon_type' => 'nullable|string',
            'skill_type' => 'nullable|string',
            'content' => 'required|string',
        ]);

        $patchNote->update($validated);
        $patchNote->target_name = $this->getTargetName($patchNote);

        return new PatchNoteResource($patchNote);
    }

    public function destroy(PatchNote $patchNote)
    {
        $patchNote->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    private function getTargetName(PatchNote $note): ?string
    {
        if (!$note->target_id) {
            return null;
        }

        return match ($note->category) {
            '캐릭터' => Character::find($note->target_id)?->name,
            '아이템' => Equipment::find($note->target_id)?->name,
            '특성' => GameTrait::find($note->target_id)?->name,
            '전술스킬' => TacticalSkill::find($note->target_id)?->name,
            default => null,
        };
    }
}
