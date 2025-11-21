<?php

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
