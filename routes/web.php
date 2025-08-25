<?php

use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\EquipmentFirstController;
use App\Http\Controllers\MainController;
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


Route::get('/', [MainController::class, 'index']);

Route::get('/main', [MainController::class, 'index']);
Route::get('/equipment', [EquipmentController::class, 'index']);
Route::get('/equipment-first', [EquipmentFirstController::class, 'index']);
Route::get('/detail/{types}', [MainController::class, 'show']);
