<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\TacticalSkillResource;
use App\Models\TacticalSkill;
use Illuminate\Http\Request;

class TacticalSkillController extends Controller
{
    public function index()
    {
        $skills = TacticalSkill::orderBy('name')->get();
        return TacticalSkillResource::collection($skills);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tooltip' => 'nullable|string',
        ]);

        $skill = TacticalSkill::create($validated);
        return new TacticalSkillResource($skill);
    }

    public function show(TacticalSkill $tacticalSkill)
    {
        return new TacticalSkillResource($tacticalSkill);
    }

    public function update(Request $request, TacticalSkill $tacticalSkill)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tooltip' => 'nullable|string',
        ]);

        $tacticalSkill->update($validated);
        return new TacticalSkillResource($tacticalSkill);
    }

    public function destroy(TacticalSkill $tacticalSkill)
    {
        $tacticalSkill->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
