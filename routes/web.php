<?php

use App\Http\Controllers\CharacterController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\EquipmentFirstController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\TraitController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Redirect login to React admin login
Route::get('/login', function() {
    return redirect('/admin/login');
})->name('login');

Route::get('/', [MainController::class, 'index']);

Route::get('/character', [CharacterController::class, 'index']);
Route::get('/equipment', [EquipmentController::class, 'index']);
Route::get('/equipment-first', [EquipmentFirstController::class, 'index']);
Route::get('/trait', [TraitController::class, 'index']);
Route::get('/detail/{types}', [CharacterController::class, 'show']);

// React Admin SPA - catch all admin routes
Route::get('/admin/{any?}', function () {
    return view('admin');
})->where('any', '.*');
