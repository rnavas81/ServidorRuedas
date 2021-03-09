<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rueda_viajes_usuario;
use App\Models\User;
use App\Models\AsignacionRol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
// use Kreait\Laravel\Firebase\Facades\Firebase;

class Usuarios extends Controller {

    /**
     * @param $id
     * @param Request $paras
     * Solicitud de un usuario para unirse a una rueda
     */
    public function solicitarRueda($id, Request $params) {
        echo "probando";
    }

    /**
     * @param $id identificador del usuario que se une
     * @param Request $params contiene los datos de la rueda a la que se une y el horario
     */
    public function unirseRueda(Request $params) {
        $idUsuario = $params->get("idUser");
        $idRueda = $params->get("idRueda");
        $horario = $params->get("horario");
        // Borra los posibles viajes del usuario para esa rueda
        \DB::select("DELETE FROM ruedas_viajes_users WHERE id_usuario='" . $idUsuario . "';");
        // Agrega los viajes
        foreach ($horario as $item) {
            foreach ($item as $viaje){
                Rueda_viajes_usuario::create([
                    'id_rueda_viaje'=>$viaje['id'],
                    'id_usuario'=>$idUsuario,
                    'reglas'=>json_encode($viaje["reglas"]),
                ]);
            }
        }
        User::where("id",$idUsuario)->update(["rueda"=>$idRueda]);

        app('App\Http\Controllers\Api\Ruedas')->generateRueda($idRueda);

        return response()->json([
                    'message' => 'Ok',
                    'data'=>User::where("id",$idUsuario)->first(),
                        ], 201);
    }

    public function comprobarEstado(Request $params) {
        $id = $params->get('idUser');
        $user = User::with('ruedas')->where("id",$id)->first();
        return response()->json([
                    'registered' => count($user->ruedas) > 0
                        ], 200);
    }

    public function crearUsuario(Request $request) {

        $user = new User([
            'name' => $request->name,
            'surname' => $request->surname,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);
        $user->status = 1;
        if ($user->save()) {
            $response = response()->json([
                'message' => 'Creacion satisfactoria',
                'code' => '200'
                    ], 200);
        } else {
            $response = response()->json([
                'message' => 'Error del servidor',
                'code' => '500'
                    ], 500);
        }

        $idUsuario = DB::table('users')
                ->select('id')
                ->where('email', '=', $user->email)
                ->get();

        $asignacionRol = new AsignacionRol([
            'idUsuario' => $idUsuario[0]->id,
            'rol' => $request->rol,
        ]);

        $asignacionRol->save();

        return response()->json([
                    'message' => 'Creacion satisfactoria',
                    'code' => '201'
                        ], 201);
    }

    public function getUsers() {
        $usuarios = User::where("status",1)
                        ->get();
        return response()->json([
                    'listaUsuarios' => ($usuarios)
                        ], 200);
    }

    public function editUser(Request $request) {
        //Si no cambia la contraseña
        if ($request->editPassword2 == null) {
            $editUser = DB::table('users')
                    ->where('id', $request->idUsuario)
                    ->update(['name' => $request->editName,
                'surname' => $request->editSurname,
                'email' => $request->editEmail]);
        } else {
            //Si pone una contraseña nueva
            $editUser = DB::table('users')
                    ->where('id', $request->idUsuario)
                    ->update(['name' => $request->editName,
                'surname' => $request->editSurname,
                'email' => $request->editEmail,
                'password' => bcrypt($request->editPassword2)]);
        }

        $editRol = DB::table('asignacion_rols')
                ->where('idUsuario', $request->idUsuario)
                ->update(['rol' => $request->editRol]);

        return response()->json([
                    'editado' => ('OK')
                        ], 200);
    }

    public function deleteUser(Request $request) {
        if($request->user()->id != $request->id){
           $this->delete($request);
        }else{
            return response()->json(null, 405);
        }
        
        // DB::table('asignacion_rols')
        //         ->where('idUsuario', $request->id)
        //         ->delete();

        
    }
    
    // Funcion para dar de baja la cuenta
    public function delete(Request $request){
        $user = $request->user();
        $user->status = 0;
        $user->save();
        
        app('App\Http\Controllers\Api\Ruedas')->generateRueda($request->rueda);
        
        return response()->json([
            'borrado' => ('OK')
        ], 200);
    }

    // Funcion para editar el perfil
    public function modify(Request $request) {
        
        if ($user = User::find($request->id)) {
            //Modificamos sus campos normales
            if ($request->password == null) {
                $request->validate([
                    'name' => 'required|string',
                    'surname' => 'required|string',
                    'email' => 'required|string'
                ]);
                $user->name = $request->name;
                $user->surname = $request->surname;
                $user->email = $request->email;
            } else {
                $request->validate([
                    'name' => 'required|string',
                    'surname' => 'required|string',
                    'email' => 'required|string',
                    'password' => 'required_with:password2|same:password2',
                    'password2' => 'required'
                ]);
                if (isset($errors) && $errors->any()) {
                    return "contiene errores";
                }
                $user->name = $request->name;
                $user->surname = $request->surname;
                $user->email = $request->email;
                $user->password = bcrypt($request->password);
            }
            
            // Codigo subir imagen dropbox
            if ($request->hasFile('image')) {
                
                $dropbox = Storage::disk('dropbox')->getDriver()->getAdapter()->getClient();
                
                $oldName = $user->file;
                if ($user->file != '' ) {
                    $dropbox->delete($user->file);
                }
                
                $name = Crypt::encrypt($user->id);
                $name = substr($name, 9, 12);
                $name = $name .'.'. $request->file('image')->getClientOriginalExtension();  
                
                Storage::disk('dropbox')->putFileAs(
                        '/',
                        $request->file('image'),
                        $name
                );

                $response = $dropbox->createSharedLinkWithSettings(
                        $name,
                        ["requested_visibility" => "public"]
                );
                $url = str_replace('dl=0', 'raw=1', $response['url']);
                $user->avatar = $url;
                $user->file = $name;
            }else{
                $url = $user->avatar;
            }
            
            $user->save();
            return response()->json([
                    'mensaje' => 'Modificación exitosa',
                    'status' => 200,
                    'avatar' => $url
                        ], 200);
        }else{
            return response()->json([
                    'mensaje' => 'Error con el usuario',
                    'status' => 400
                        ], 400);
        }
    }
    
    // Funcion para obtemer el rol del usuario
    public function user(Request $request)
    {
        $user = $request->user();
        $rol = AsignacionRol::with("roles","users")
            ->where('idUsuario',$user->id)
            ->first();
        return response()->json([
            'rol' => $rol->roles->id,
        ], 200);
    }

}
