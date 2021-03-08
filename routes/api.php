<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;

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
Route::post('/test0',function (Request $params){

    $name = Crypt::encrypt('test');
    $name = substr($name, 9, 12);
    $name = $name .'.'. $params->file('img')->getClientOriginalExtension();

    Storage::disk('dropbox')->putFileAs(
        '/',
        $params->file('img'),
        $name
    );

    $dropbox = Storage::disk('dropbox')->getDriver()->getAdapter()->getClient();

    $response = $dropbox->createSharedLinkWithSettings(
            $name,
            ["requested_visibility" => "public"]
        );
    $url = str_replace('dl=0', 'raw=1', $response['url']);

    return $url;
});
//Usuario

// Primera version de editar perfil
Route::post('/usuario/edit',[App\Http\Controllers\Api\Usuarios::class,'edit']);

// Ruta con problema con passport
Route::post('/usuario/modify',[App\Http\Controllers\Api\Usuarios::class,'modify']);

// Ruta de prueba solo para el examen
Route::post('/usuario/img',[App\Http\Controllers\Api\Usuarios::class,'upImg']);

// Ruta validacion de email
Route::get('/check/{clave}',[App\Http\Controllers\Auth\AuthController2::class,'check']);

// rutas formularios iniciales
Route::post('/signup', [App\Http\Controllers\Auth\AuthController2::class, 'signup'])->name('signup');
Route::post('/login', [App\Http\Controllers\Auth\AuthController2::class, 'login'])->name('login');
Route::post('/forget', [App\Http\Controllers\Auth\AuthController2::class, 'forget'])->name('forget');


// Rutas utilizando passport
Route::group([], function () {
//    Route::post('login', 'AuthController@login');
//    Route::post('signup', 'AuthController@signUp');
    Route::get('/test',function (Request $params){
        return true;
    });
    Route::group([
      'middleware' => 'auth:api'
    ], function() {
//      Route::get('logout', 'AuthController@logout');
        Route::get('/test1',function (Request $request){
            return $request->user()->id;
        });


        //  RUTAS
        //  Para unirte a una rueda
        Route::post('/usuario/unirse',[App\Http\Controllers\Api\Usuarios::class,'unirseRueda']);
        //  Para dar de baja tu cuenta
        Route::post('/usuario/deleteAccount', [App\Http\Controllers\Api\Usuarios::class, 'delete']);
        //  Para comprobar el estado del usuario
        Route::post('/usuario/estado',[App\Http\Controllers\Api\Usuarios::class,'comprobarEstado']);
        
        // Para comprobar que el usuario esta logeado
        Route::post('/usuario/test',function (Request $params){
            return true;
//            return response()->json([
//                    'message' => 'Ok'
//                        ], 200);
        });

        // Para comprobar el rol del usuario
        Route::post('/usuario/testRol',[App\Http\Controllers\Api\Usuarios::class,'user']);
        Route::post('/usuario/deleteAccount', [App\Http\Controllers\Api\Usuarios::class, 'delete']);
        
        // Para hacer el logout
        Route::post('/logout', [App\Http\Controllers\Auth\AuthController2::class, 'logout'])->name('logout');


        // Rutas adminstracion
        Route::group([
            'middleware' => 'rolMidd:api'
        ], function (){
            // Administracion de usuarios
            Route::post('/administrador/createUser', [App\Http\Controllers\Api\Usuarios::class, 'crearUsuario']);
            Route::get('/administrador/getUsers', [App\Http\Controllers\Api\Usuarios::class, 'getUsers']);
            Route::post('/administrador/editUser', [App\Http\Controllers\Api\Usuarios::class, 'editUser']);
            Route::post('/administrador/deleteUser', [App\Http\Controllers\Api\Usuarios::class, 'deleteUser']);
        });


        //Ruedas
        Route::get('/rueda',[App\Http\Controllers\Api\Ruedas::class,'getAll']);
        Route::get('/rueda/{id}',[App\Http\Controllers\Api\Ruedas::class,'getRueda']);
        Route::post('/rueda',[App\Http\Controllers\Api\Ruedas::class,'addRueda']);
        Route::put('/rueda',[App\Http\Controllers\Api\Ruedas::class,'updateRueda']);
        Route::delete('/rueda/{id}',[App\Http\Controllers\Api\Ruedas::class,'deleteRueda']);
        // Para generar la rueda
        Route::get('/rueda/generar',[App\Http\Controllers\Api\Ruedas::class,'generateRueda']);
        Route::get('/rueda/generar/{id}',[App\Http\Controllers\Api\Ruedas::class,'generateRueda']);
        // Para obtener la rueda
        Route::get('/rueda/generada', [App\Http\Controllers\Api\Ruedas::class, 'getRuedaGenerada']);
        Route::get('/rueda/generada/{id}', [App\Http\Controllers\Api\Ruedas::class, 'getRuedaGenerada']);
    });
});
