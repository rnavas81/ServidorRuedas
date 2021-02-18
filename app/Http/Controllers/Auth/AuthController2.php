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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class AuthController2 extends Controller
{
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
        $user->save();
        return response()->json([
            'message' => 'Creacion satisfactoria',
            'code' => '201'
        ], 201);
    }

    public function login2(Request $request)
    {
        $request->validate([
            'email'       => 'required|string|email',
            'password'    => 'required|string',
            'remember_me' => 'boolean',
        ]);
        $credentials = request(['email', 'password']);
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Unauthorized'], 401);
        }
        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;
        if ($request->remember_me) {
            $token->expires_at = Carbon::now()->addWeeks(1);
        }
        $token->save();
        return response()->json([
            'access_token' => $tokenResult->accessToken,
            'token_type'   => 'Bearer',
            'expires_at'   => Carbon::parse(
                $tokenResult->token->expires_at)
                    ->toDateTimeString(),
        ]);
    }

    public function prueba(Request $request){
        $rol = AsignacionRol::with(["roles","users"])->first();
    }

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
        $accessToken = auth()->user()->createToken('authToken')->accessToken;

        //return response(['user' => auth()->user(), 'access_token' => $accessToken]);
//        return response()->json(['message' => ['user' => auth()->user(), 'access_token' => $accessToken], 'code' => 200], 200);

        $rol = AsignacionRol::with("roles","users")
            ->where('idUsuario',$user->id)
            ->first();

        $return = [
            'message' => 'Login correcto',
            'id' => $user->id,
            'name' => $user->name,
            'surname' => $user->surname,
            'mail' => $user->email,
            'access_token' => $accessToken,
            'rol' => $rol->roles->id,
        ];

        return response()->json($return, 200);
    }
    
    public function forget(Request $request) {
        $data = $request->validate([
            'email' => 'email|required'
        ]);
       
        
        $user = User::where('email',$data['email'])->first();
        if ($user != null) {
            $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $pass = substr(str_shuffle($permitted_chars), 0, 10);
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
    
}
