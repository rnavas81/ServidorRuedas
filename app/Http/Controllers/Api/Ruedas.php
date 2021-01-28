<?php

namespace App\Http\Controllers;

use App\Models\rueda;
use App\Models\Rueda_viaje;
use Illuminate\Http\Request;

class Ruedas extends Controller
{
    /*
     * Devuelve una rueda básica
     */
    public function getRueda($id=null){
//        if(isset($id)){
//            $rueda = [];
//        } else {
//        }
        $rueda = Rueda::with("viajes")->first();
        return $rueda;

        return response()->json($rueda, 200);

    }
}
