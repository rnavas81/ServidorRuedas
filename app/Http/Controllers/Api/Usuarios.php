<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rueda_viajes_usuario;
use App\Models\User;
use Illuminate\Http\Request;
use Kreait\Laravel\Firebase\Facades\Firebase;

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

    public function edit(Request $request) {
        if ($request->password == null) {
            $request->validate([
                'name' => 'required|string',
                'surname' => 'required|string',
                'email' => 'required|string'
            ]);
            $user = User::find($request->id);
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
            $user = User::find($request->id);
            $user->name = $request->name;
            $user->surname = $request->surname;
            $user->email = $request->email;
            $user->password = bcrypt($request->password);
        }
        $user->save();
        return response()->json([
                    'mensaje' => 'Modificación exitosa',
                    'status' => 200
                        ], 200);
    }

    public function upImg(Request $request) {
        if ($request->hasFile('image')) {
//            $file = $request->file('image');
//            $filename = $request->id;
//            $extension = $file->getClientOriginalExtension();
//            $picture = $filename . '.' . $extension;
//            //move image to public/img folder
//            $file->move(public_path('img'), $picture);

            
            $image = $request->file('image'); //image file from frontend 
            $name = date('Ymd');
            $firebase_storage_path = '';  
            $localfolder = public_path('firebase-temp-uploads') . '/';
            $extension = $image->getClientOriginalExtension();
            $file = $name . '.' . $extension;
            if ($image->move($localfolder, $file)) {
                $uploadedfile = fopen($localfolder . $file, 'r');
                //Linea importante el resto esta de relleno y testing
                app('firebase.storage')->getBucket()->upload($uploadedfile, ['name' => $firebase_storage_path . $file,"metadata" => [  "contentType"=> 'image/png']]);
                //will remove from local laravel folder  
                unlink($localfolder . $file);
                $url = "https://firebasestorage.googleapis.com/v0/b/carshare-vdg.appspot.com/o/".$file."?alt=media";
                
                // Actualizamos la url para el usuario
                $user = User::find($request->id);
                $user->avatar = $url;
                $user->save();
                
                return response()->json(["message" => "Image Uploaded Succesfully"],200);
            } else {
               return response()->json(["message" => "Sigue sin ir"],400);
            }
        } else {
            return response()->json(["message" => "Select image first."],200);
        }
    }
    
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
            
            //Modificamos su icono
            if ($request->hasFile('image')) {
                $image = $request->file('image'); //image file from frontend 
                $name = date('Ymd');
                $firebase_storage_path = '';  
                $localfolder = public_path('firebase-temp-uploads') . '/';
                $extension = $image->getClientOriginalExtension();
                $file = $name . '.' . $extension;
                if ($image->move($localfolder, $file)) {
                    $uploadedfile = fopen($localfolder . $file, 'r');
                    //Linea importante el resto esta de relleno y testing
                    app('firebase.storage')->getBucket()->upload($uploadedfile, ['name' => $firebase_storage_path . $file,"metadata" => [  "contentType"=> 'image/png']]);
                    //will remove from local laravel folder  
                    unlink($localfolder . $file);
                    $url = "https://firebasestorage.googleapis.com/v0/b/carshare-vdg.appspot.com/o/".$file."?alt=media";

                    // Actualizamos la url para el usuario
                    $user->avatar = $url;
                }
            }else{
                $url = $user->avatar;
            }
            $user->save();
            return response()->json([
                    'mensaje' => 'Modificación exitosa',
                    'status' => 200,
                    'url' => $url
                        ], 200);
        }else{
            return response()->json([
                    'mensaje' => 'Error con el usuario',
                    'status' => 400
                        ], 400);
        }
    }

}
