<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('register', 'register');
    Route::post('logout', 'logout');
    Route::post('refresh', 'refresh');
    Route::get('me', 'me');
});
Route::controller(\App\Http\Controllers\OrdenDeTrabajoController::class)->group(function () {
    Route::get('peticiones', 'index');
    Route::get('mispeticiones', 'listmine');
    Route::get('peticiones/{id}', 'show');
    Route::post('ordendetrabajo', 'store');
    Route::get('ordendetrabajo', 'index');
    Route::put('ordendetrabajo/{id}/asignar', 'asignar');
    Route::get('/ordendetrabajo/{id}/completa', 'obtenerOrdenCompleta');

});
