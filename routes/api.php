<?php

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
// Rutas Publicas
//Ruta de prueba
Route::get('/test',function (Request $params){
    return true;
});
//Ruedas
Route::post('/usuario/unirse',[App\Http\Controllers\Api\Usuarios::class,'unirseRueda']);
Route::post('/usuario/estado',[App\Http\Controllers\Api\Usuarios::class,'comprobarEstado']);
//Usuario
Route::get('/rueda',[App\Http\Controllers\Api\Ruedas::class,'getRueda']);
Route::get('/rueda/{id}',[App\Http\Controllers\Api\Ruedas::class,'getRueda']);
Route::get('/rueda/generar',[App\Http\Controllers\Api\Ruedas::class,'generateRueda']);
Route::get('/rueda/generar/{id}',[App\Http\Controllers\Api\Ruedas::class,'generateRueda']);


Route::post('/signup', [App\Http\Controllers\Auth\AuthController2::class, 'signup'])->name('signup');
Route::post('/login', [App\Http\Controllers\Auth\AuthController2::class, 'login'])->name('login');
Route::post('/forget', [App\Http\Controllers\Auth\AuthController2::class, 'forget'])->name('forget');

Route::get('/rueda/generada/{id}', [App\Http\Controllers\Api\Ruedas::class, 'getRuedaGenerada']);
Route::get('/rueda/generada', [App\Http\Controllers\Api\Ruedas::class, 'getRuedaGenerada']);
