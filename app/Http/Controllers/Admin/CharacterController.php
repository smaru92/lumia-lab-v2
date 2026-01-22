<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\CharacterResource;
use App\Models\Character;
use App\Models\CharacterTag;
use Illuminate\Http\Request;

class CharacterController extends Controller
{
    public function index()
    {
        $characters = Character::with('tags')->orderBy('name')->get();
        return CharacterResource::collection($characters);
    }

    public function show(Character $character)
    {
        $character->load('tags');
        return new CharacterResource($character);
    }

    public function update(Request $request, Character $character)
    {
        $validated = $request->validate([
            'name' => 'nullable|string',
            'max_hp' => 'nullable|numeric',
            'max_hp_by_lv' => 'nullable|numeric',
            'max_mp' => 'nullable|numeric',
            'max_mp_by_lv' => 'nullable|numeric',
            'init_extra_point' => 'nullable|numeric',
            'max_extra_point' => 'nullable|numeric',
            'attack_power' => 'nullable|numeric',
            'attack_power_by_lv' => 'nullable|numeric',
            'deffence' => 'nullable|numeric',
            'deffence_by_lv' => 'nullable|numeric',
            'hp_regen' => 'nullable|numeric',
            'hp_regen_by_lv' => 'nullable|numeric',
            'sp_regen' => 'nullable|numeric',
            'sp_regen_by_lv' => 'nullable|numeric',
            'attack_speed' => 'nullable|numeric',
            'attack_speed_limit' => 'nullable|numeric',
            'attack_speed_min' => 'nullable|numeric',
            'move_speed' => 'nullable|numeric',
            'sight_range' => 'nullable|numeric',
        ]);

        $character->update($validated);
        $character->load('tags');

        return new CharacterResource($character);
    }

    public function syncTags(Request $request, Character $character)
    {
        $validated = $request->validate([
            'tag_ids' => 'array',
            'tag_ids.*' => 'exists:character_tags,id',
            'new_tags' => 'array',
            'new_tags.*' => 'string|max:255',
        ]);

        $tagIds = $validated['tag_ids'] ?? [];

        // Create new tags if provided
        if (!empty($validated['new_tags'])) {
            foreach ($validated['new_tags'] as $tagName) {
                $tag = CharacterTag::firstOrCreate(['name' => trim($tagName)]);
                $tagIds[] = $tag->id;
            }
        }

        $character->tags()->sync(array_unique($tagIds));
        $character->load('tags');

        return new CharacterResource($character);
    }
}
