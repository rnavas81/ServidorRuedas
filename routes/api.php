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
    return redirect('https://www.google.es');
});
//Usuario
Route::post('/usuario/unirse',[App\Http\Controllers\Api\Usuarios::class,'unirseRueda']);
Route::post('/usuario/estado',[App\Http\Controllers\Api\Usuarios::class,'comprobarEstado']);
Route::post('/usuario/edit',[App\Http\Controllers\Api\Usuarios::class,'edit']);
Route::post('/usuario/modify',[App\Http\Controllers\Api\Usuarios::class,'modify']);
Route::post('/usuario/img',[App\Http\Controllers\Api\Usuarios::class,'upImg']);
//Ruedas
Route::get('/rueda',[App\Http\Controllers\Api\Ruedas::class,'getAll']);
Route::get('/rueda/{id}',[App\Http\Controllers\Api\Ruedas::class,'getRueda']);
Route::get('/rueda/generar',[App\Http\Controllers\Api\Ruedas::class,'generateRueda']);
Route::get('/rueda/generar/{id}',[App\Http\Controllers\Api\Ruedas::class,'generateRueda']);
Route::post('/rueda',[App\Http\Controllers\Api\Ruedas::class,'addRueda']);
Route::put('/rueda',[App\Http\Controllers\Api\Ruedas::class,'updateRueda']);
Route::delete('/rueda/{id}',[App\Http\Controllers\Api\Ruedas::class,'deleteRueda']);

Route::get('/check/{clave}',[App\Http\Controllers\Auth\AuthController2::class,'check']);

Route::post('/signup', [App\Http\Controllers\Auth\AuthController2::class, 'signup'])->name('signup');
Route::post('/login', [App\Http\Controllers\Auth\AuthController2::class, 'login'])->name('login');
Route::post('/forget', [App\Http\Controllers\Auth\AuthController2::class, 'forget'])->name('forget');


Route::get('/rueda/generada/{id}', [App\Http\Controllers\Api\Ruedas::class, 'getRuedaGenerada']);
Route::get('/rueda/generada', [App\Http\Controllers\Api\Ruedas::class, 'getRuedaGenerada']);


Route::post('/administrador/createUser', [App\Http\Controllers\Api\Usuarios::class, 'crearUsuario']);
Route::get('/administrador/getUsers', [App\Http\Controllers\Api\Usuarios::class, 'getUsers']);
Route::post('/administrador/editUser', [App\Http\Controllers\Api\Usuarios::class, 'editUser']);
Route::post('/administrador/deleteUser', [App\Http\Controllers\Api\Usuarios::class, 'deleteUser']);
