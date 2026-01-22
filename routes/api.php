<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CharacterController as AdminCharacterController;
use App\Http\Controllers\Admin\CharacterTagController;
use App\Http\Controllers\Admin\EquipmentController as AdminEquipmentController;
use App\Http\Controllers\Admin\EquipmentSkillController;
use App\Http\Controllers\Admin\VersionHistoryController;
use App\Http\Controllers\Admin\PatchNoteController;
use App\Http\Controllers\Admin\TraitController as AdminTraitController;
use App\Http\Controllers\Admin\TacticalSkillController as AdminTacticalSkillController;
use App\Http\Controllers\InfoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Admin API Routes
Route::prefix('admin')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);

        // Characters (read/update only)
        Route::get('/characters', [AdminCharacterController::class, 'index']);
        Route::get('/characters/{character}', [AdminCharacterController::class, 'show']);
        Route::put('/characters/{character}', [AdminCharacterController::class, 'update']);
        Route::post('/characters/{character}/tags', [AdminCharacterController::class, 'syncTags']);

        // Character Tags
        Route::get('/character-tags', [CharacterTagController::class, 'index']);
        Route::post('/character-tags', [CharacterTagController::class, 'store']);
        Route::delete('/character-tags/{characterTag}', [CharacterTagController::class, 'destroy']);

        // Equipment (read/update only)
        Route::get('/equipment', [AdminEquipmentController::class, 'index']);
        Route::get('/equipment/{equipment}', [AdminEquipmentController::class, 'show']);
        Route::put('/equipment/{equipment}', [AdminEquipmentController::class, 'update']);
        Route::post('/equipment/{equipment}/skills', [AdminEquipmentController::class, 'syncSkills']);

        // Equipment Skills (full CRUD)
        Route::apiResource('equipment-skills', EquipmentSkillController::class);

        // Version Histories (full CRUD)
        Route::apiResource('version-histories', VersionHistoryController::class);

        // Patch Notes (nested under version-histories)
        Route::get('/version-histories/{versionHistory}/patch-notes', [PatchNoteController::class, 'index']);
        Route::post('/version-histories/{versionHistory}/patch-notes', [PatchNoteController::class, 'store']);
        Route::put('/patch-notes/{patchNote}', [PatchNoteController::class, 'update']);
        Route::delete('/patch-notes/{patchNote}', [PatchNoteController::class, 'destroy']);

        // Options for PatchNote form
        Route::get('/traits', [AdminTraitController::class, 'index']);
        Route::get('/tactical-skills', [AdminTacticalSkillController::class, 'index']);
    });
});

// 정보 업데이트
Route::get('/character', [InfoController::class, 'getCharacters'])->name('api.character');
Route::get('/equipment', [InfoController::class, 'getEquipments'])->name('api.equipment');
Route::get('/item', [InfoController::class, 'getItems']);
Route::get('/skill', [InfoController::class, 'getSkills']);
Route::get('/trait', [InfoController::class, 'getTraits']);

// Detail 페이지 Lazy Loading API
Route::get('/detail/{types}/tiers', [\App\Http\Controllers\CharacterController::class, 'getDetailTiers']);
Route::get('/detail/{types}/ranks', [\App\Http\Controllers\CharacterController::class, 'getDetailRanks']);
Route::get('/detail/{types}/tactical-skills', [\App\Http\Controllers\CharacterController::class, 'getDetailTacticalSkills']);
Route::get('/detail/{types}/equipment', [\App\Http\Controllers\CharacterController::class, 'getDetailEquipment']);
Route::get('/detail/{types}/traits', [\App\Http\Controllers\CharacterController::class, 'getDetailTraits']);
Route::get('/detail/{types}/trait-combinations', [\App\Http\Controllers\CharacterController::class, 'getDetailTraitCombinations']);

// 메인페이지 패치 비교 API
Route::get('/patch-comparison', [\App\Http\Controllers\MainController::class, 'getPatchComparison']);
