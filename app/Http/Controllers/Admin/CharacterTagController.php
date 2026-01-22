<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CharacterTag;
use Illuminate\Http\Request;

class CharacterTagController extends Controller
{
    public function index()
    {
        $tags = CharacterTag::orderBy('name')->get();
        return response()->json(['data' => $tags]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:character_tags,name',
        ]);

        $tag = CharacterTag::create($validated);

        return response()->json(['data' => $tag], 201);
    }

    public function destroy(CharacterTag $characterTag)
    {
        $characterTag->delete();
        return response()->json(null, 204);
    }
}
