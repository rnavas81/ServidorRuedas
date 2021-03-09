<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\AsignacionRol;
use App\Models\Rol;
use Carbon\Carbon;
use App\Mail\RecuperarContraseña;
use App\Mail\Verificar;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class AuthController2 extends Controller
{
    // Funcion limpia de registro
    public function signup(Request $request)
    {
        $request->validate([
            'name'     => 'required|string',
            'surname'  => 'required|string',
            'email'    => 'required|string|email|unique:users',
            'password' => 'required|string',
        ]);
        $user = new User([
            'name'     => $request->name,
            'surname'  => $request->surname,
            'email'    => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $code = $this->generarAlfanumerico(0, 15);
        $user->remember_token = $code;

        $url = $_SERVER['SERVER_NAME'] . ($_SERVER['SERVER_PORT']!=80?$_SERVER['SERVER_PORT']:'') .  DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "api" . DIRECTORY_SEPARATOR . "check" . DIRECTORY_SEPARATOR . $code;


        Mail::to($user->email)->send(new Verificar($user->name, $user->surname, $url));
        if (!Mail::failures()) {
            $user->status = 1;
            $user->save();
            AsignacionRol::create([
                'idUsuario'=>$user->id,
                'rol'=>2
            ]);
            return response()->json([
                'message' => 'Creacion satisfactoria, verifique su email.',
                'code' => '201'
            ], 201);
        } else {
           return response()->json([
            'message' => 'Error del sistema'
        ], 500);
        }
    }

    // Funcion de validar email
    public function check($clave) {
        $user = User::where('remember_token',$clave)->first();
        if ($user != null) {
            $user->email_verified_at = time();
            $user->remember_token = null;
            $user->save();
            return redirect(env("APP_ROUTE"));
        }

    }
    
    // Funcion reutilizada para generar un alfanumerico
    public function generarAlfanumerico($val1, $val2){
        $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $string = substr(str_shuffle($permitted_chars), $val1, $val2);
        
        return $string;
    }

    // Funcion que devuelve el rol de usuario
    public function prueba(Request $request){
        $rol = AsignacionRol::with(["roles","users"])->first();
    }

    // Funcion de login
    public function login(Request $request){
        $loginData = $request->validate([
            'email' => 'email|required',
            'password' => 'required'
        ]);

        if (!auth()->attempt($loginData)) {
            //return response(['message' => 'Login incorrecto. Revise las credenciales.'], 400);
            return response()->json(['message' => 'Login incorrecto. Revise las credenciales.'], 400);
        }
        $user = auth()->user();
        if ($user->email_verified_at == null) {
            return response()->json(['message' => 'Correo sin verificar'], 400);
        }
        if ($user->status == 0) {
             return response()->json(['message' => 'Login incorrecto. Revise las credenciales.'], 400);
        }
        $accessToken = auth()->user()->createToken('authToken')->accessToken;


        $rol = AsignacionRol::with("roles","users")
            ->where('idUsuario',$user->id)
            ->first();

        $return = [
            'message' => 'Login correcto',
            'id' => $user->id,
            'name' => $user->name,
            'surname' => $user->surname,
            'email' => $user->email,
            'access_token' => $accessToken,
            'avatar' => $user->avatar,
            'rol' => $rol->roles->id,
            'rueda' => $user->rueda,
        ];
        
        return response()->json($return, 200);
    }

    // Funcion para recuperar la contraseña
    public function forget(Request $request) {
        $data = $request->validate([
            'email' => 'email|required'
        ]);


        $user = User::where('email',$data['email'])->first();
        if ($user != null) {
            
            $pass = $this->generarAlfanumerico(0, 10);
            $user->password = bcrypt($pass);
            $user->save();

            Mail::to($data['email'])->send(new RecuperarContraseña($pass));
            if (!Mail::failures()) {
                return response()->json([
                'message' => 'Compruebe su correo electronico'
            ], 200);
            } else {
               return response()->json([
                'message' => 'Error del sistema'
            ], 500);
            }

        }else{
            return response()->json([
                'message' => 'Compruebe su correo electronico'
            ], 200);
        }
    }

    // Funcion para realizar el cerrar sesion
    public function logout(Request $request){
        $request->user()->token()->revoke();

        return response()->json([
            'message' => 'Successfully logged out'
        ],200);
    }
}
