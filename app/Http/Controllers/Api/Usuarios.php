<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rueda_viajes_usuario;
use App\Models\User;
use App\Models\AsignacionRol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $idRueda = $params->get("id_rueda");
        $horario = $params->get("horario");
        // Borra los posibles viajes del usuario para esa rueda
        \DB::select("DELETE FROM ruedas_viajes_users WHERE id_usuario='" . $idUsuario . "' AND id_rueda_viaje IN (SELECT id FROM ruedas_viajes WHERE ruedas_viajes.id_rueda='" . $idRueda . "')");
        // Agrega los viajes
        foreach ($horario as $item) {
            foreach ($item as $id) {
                Rueda_viajes_usuario::create([
                    'id_rueda_viaje' => $id,
                    'id_usuario' => $idUsuario,
                    'reglas' => "",
                ]);
            }
        }

        app('App\Http\Controllers\Api\Ruedas')->generateRueda($id);
        return response()->json([
                    'message' => 'Ok',
                        ], 201);
    }

    public function comprobarEstado(Request $params) {
        $id = $params->get('idUser');
        $user = User::with('ruedas')->where("id", $id)->first();
//        dd(count($user->ruedas));
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
        $usuarios = DB::table('users')
                ->join('asignacion_rols', 'users.id', '=', 'asignacion_rols.idUsuario')
                ->select(
                        'users.id',
                        'users.name',
                        'users.surname',
                        'users.email',
                        'asignacion_rols.rol',
                )
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
        DB::table('users')
                ->where('id', $request->id)
                ->delete();

        DB::table('asignacion_rols')
                ->where('idUsuario', $request->id)
                ->delete();

        return response()->json([
                    'borrado' => ('OK')
                        ], 200);
    }

}
