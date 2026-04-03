<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\CharacterResource;
use App\Models\Character;
use App\Models\CharacterTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function syncWeaponTags(Request $request, Character $character)
    {
        $validated = $request->validate([
            'weapon_type' => 'required|string|max:100',
            'tag_ids' => 'array',
            'tag_ids.*' => 'exists:character_tags,id',
            'new_tags' => 'array',
            'new_tags.*' => 'string|max:255',
        ]);

        $tagIds = $validated['tag_ids'] ?? [];
        $weaponType = $validated['weapon_type'];

        if (!empty($validated['new_tags'])) {
            foreach ($validated['new_tags'] as $tagName) {
                $tag = CharacterTag::firstOrCreate(['name' => trim($tagName)]);
                $tagIds[] = $tag->id;
            }
        }

        $tagIds = array_unique($tagIds);

        // Sync: delete existing for this combo, then insert new
        DB::table('character_weapon_character_tag')
            ->where('character_id', $character->id)
            ->where('weapon_type', $weaponType)
            ->delete();

        foreach ($tagIds as $tagId) {
            DB::table('character_weapon_character_tag')->insert([
                'character_id' => $character->id,
                'weapon_type' => $weaponType,
                'character_tag_id' => $tagId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $tags = CharacterTag::whereIn('id', $tagIds)->get();

        return response()->json([
            'character_id' => $character->id,
            'weapon_type' => $weaponType,
            'tags' => $tags,
        ]);
    }

    public function getWeaponTags(Character $character)
    {
        $rows = DB::table('character_weapon_character_tag as cwct')
            ->join('character_tags as ct', 'ct.id', '=', 'cwct.character_tag_id')
            ->where('cwct.character_id', $character->id)
            ->select('cwct.weapon_type', 'ct.id as tag_id', 'ct.name as tag_name')
            ->orderBy('cwct.weapon_type')
            ->get();

        $grouped = $rows->groupBy('weapon_type')->map(function ($items) {
            return $items->map(fn($r) => ['id' => $r->tag_id, 'name' => $r->tag_name]);
        });

        return response()->json([
            'character_id' => $character->id,
            'weapon_tags' => $grouped,
        ]);
    }
}
