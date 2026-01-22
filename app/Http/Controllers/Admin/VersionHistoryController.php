<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\VersionHistoryResource;
use App\Models\VersionHistory;
use Illuminate\Http\Request;

class VersionHistoryController extends Controller
{
    public function index()
    {
        $versions = VersionHistory::orderBy('start_date', 'desc')->get();
        return VersionHistoryResource::collection($versions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'version_season' => 'nullable|integer',
            'version_major' => 'required|integer|min:0',
            'version_minor' => 'required|integer|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $version = VersionHistory::create($validated);

        return new VersionHistoryResource($version);
    }

    public function show(VersionHistory $versionHistory)
    {
        return new VersionHistoryResource($versionHistory);
    }

    public function update(Request $request, VersionHistory $versionHistory)
    {
        $validated = $request->validate([
            'version_season' => 'nullable|integer',
            'version_major' => 'required|integer|min:0',
            'version_minor' => 'required|integer|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $versionHistory->update($validated);

        return new VersionHistoryResource($versionHistory);
    }

    public function destroy(VersionHistory $versionHistory)
    {
        $versionHistory->patchNotes()->delete();
        $versionHistory->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}
