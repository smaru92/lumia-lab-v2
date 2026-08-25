<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\TraitResource;
use App\Models\GameTrait;
use App\Services\TraitGroupService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TraitController extends Controller
{
    public function __construct(private TraitGroupService $traitGroupService)
    {
    }

    public function index()
    {
        $traits = GameTrait::orderBy('name')->get();
        return TraitResource::collection($traits);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        $trait = GameTrait::create($validated);

        // 그룹은 패치마다 바뀌므로 현재 버전 기준으로 이력을 남긴다.
        $this->traitGroupService->recordChange($trait->id, $trait->trait_group);

        return new TraitResource($trait);
    }

    public function show(GameTrait $trait)
    {
        return new TraitResource($trait);
    }

    public function update(Request $request, GameTrait $trait)
    {
        $validated = $this->validated($request);

        $groupChanged = ($trait->trait_group ?? null) !== ($validated['trait_group'] ?? null);

        $trait->update($validated);

        if ($groupChanged) {
            $this->traitGroupService->recordChange($trait->id, $trait->trait_group);
        }

        return new TraitResource($trait);
    }

    public function destroy(GameTrait $trait)
    {
        $trait->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }

    /**
     * 검증 + 그룹으로부터 is_main 파생
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tooltip' => 'nullable|string',
            'category' => 'nullable|string|max:255',
            'trait_group' => ['nullable', 'string', Rule::in(TraitGroupService::GROUPS)],
        ]);

        // is_main 은 그룹에서 파생시켜 두 값이 어긋나지 않게 한다.
        if (array_key_exists('trait_group', $validated) && $validated['trait_group']) {
            $validated['is_main'] = $validated['trait_group'] === 'main' ? 1 : 0;
        }

        return $validated;
    }
}
