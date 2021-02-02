<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rueda_viajes_usuario;
use App\Models\User;
use Illuminate\Http\Request;

class Usuarios extends Controller
{
    /**
     * @param $id
     * @param Request $paras
     * Solicitud de un usuario para unirse a una rueda
     */
    public function solicitarRueda($id,Request $params){
        echo "probando";
    }

    /**
     * @param $id identificador del usuario que se une
     * @param Request $params contiene los datos de la rueda a la que se une y el horario
     */
    public function unirseRueda(Request $params){
        $idUsuario = $params->get("idUser");
        $idRueda = $params->get("idRueda");
        $horario = $params->get("horario");
        // Borra los posibles viajes del usuario para esa rueda
        \DB::select("DELETE FROM ruedas_viajes_users WHERE id_usuario='".$idUsuario."' AND id_rueda_viaje IN (SELECT id FROM ruedas_viajes WHERE idRueda='".$idRueda."')");
        // Agrega los viajes
        foreach ($horario as $item) {
            foreach ($item as $id){
                Rueda_viajes_usuario::create([
                    'id_rueda_viaje'=>$id,
                    'id_usuario'=>$idUsuario,
                    'reglas'=>"",
                ]);
            }
        }

        app('App\Http\Controllers\Ruedas')->generateRueda($id);
        return response()->json([
            'message' => 'Ok',
        ], 201);
    }

    public function comprobarEstado(Request $params){
        $id = $params->get('idUser');
        $user = User::with('ruedas')->where("id","=",$id)->first();
//        dd(count($user->ruedas));
        return response()->json([
            'registered'=>count($user->ruedas)>0
        ], 200);
    }
}
