<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\AuthController;

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
use App\Http\Controllers\Api\Usuarios;
use \App\Http\Controllers\Ruedas;

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
// Rutas Publicas
//Ruta de prueba
Route::get('/test',function (Request $params){
    $users = \App\Models\User::all();
    dd($users);
});
//Ruedas
Route::post('/usuario/unirse',[Usuarios::class,'unirseRueda']);
Route::post('/usuario/estado',[Usuarios::class,'comprobarEstado']);
//Usuario
Route::get('/rueda',[Ruedas::class,'getRueda']);
Route::get('/rueda/{id}',[Ruedas::class,'getRueda']);
Route::get('/rueda/generar',[Ruedas::class,'generateRueda']);
Route::get('/rueda/generar/{id}',[Ruedas::class,'generateRueda']);

Route::post('sigup','AuthController@signup');
Route::post('login','AuthController@login');

Route::post('/signup', [App\Http\Controllers\Auth\AuthController2::class, 'signup'])->name('signup');
Route::post('/login', [App\Http\Controllers\Auth\AuthController2::class, 'login'])->name('login');

Route::get('/rueda/generada/{id}', [Ruedas::class, 'getRuedaGenerada']);
Route::get('/rueda/generada', [Ruedas::class, 'getRuedaGenerada']);