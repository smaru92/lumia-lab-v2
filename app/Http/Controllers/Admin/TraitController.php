<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\TraitResource;
use App\Models\GameTrait;
use Illuminate\Http\Request;

class TraitController extends Controller
{
    public function index()
    {
        $traits = GameTrait::orderBy('name')->get();
        return TraitResource::collection($traits);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tooltip' => 'nullable|string',
            'is_main' => 'nullable|integer',
            'category' => 'nullable|string|max:255',
        ]);

        $trait = GameTrait::create($validated);
        return new TraitResource($trait);
    }

    public function show(GameTrait $trait)
    {
        return new TraitResource($trait);
    }

    public function update(Request $request, GameTrait $trait)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tooltip' => 'nullable|string',
            'is_main' => 'nullable|integer',
            'category' => 'nullable|string|max:255',
        ]);

        $trait->update($validated);
        return new TraitResource($trait);
    }

    public function destroy(GameTrait $trait)
    {
        $trait->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
