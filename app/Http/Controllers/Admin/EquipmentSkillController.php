<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\EquipmentSkillResource;
use App\Models\EquipmentSkill;
use Illuminate\Http\Request;

class EquipmentSkillController extends Controller
{
    public function index()
    {
        $skills = EquipmentSkill::orderBy('name')->get();
        return EquipmentSkillResource::collection($skills);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'grade' => 'nullable|string|max:255',
            'sub_category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $skill = EquipmentSkill::create($validated);

        return new EquipmentSkillResource($skill);
    }

    public function show(EquipmentSkill $equipmentSkill)
    {
        return new EquipmentSkillResource($equipmentSkill);
    }

    public function update(Request $request, EquipmentSkill $equipmentSkill)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'grade' => 'nullable|string|max:255',
            'sub_category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $equipmentSkill->update($validated);

        return new EquipmentSkillResource($equipmentSkill);
    }

    public function destroy(EquipmentSkill $equipmentSkill)
    {
        $equipmentSkill->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}
