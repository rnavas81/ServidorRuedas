<?php

namespace App\Http\Controllers;

use App\Models\Rueda;
use App\Models\Rueda_viajes_usuario;
use App\Models\RuedaGenerada;

class Ruedas extends Controller {
    /*
     * Devuelve una rueda básica
     */
    public function getRueda($id = null)
    {
//        if(isset($id)){
//            $rueda = [];
//        } else {
//        }
        $rueda = Rueda::with("viajes")->first();
        return $rueda;

        return response()->json($rueda, 200);
    }

    public function getRuedaGenerada($id = null) {
        $rueda = Rueda::with(["generada" => function($result) {
                        $result->orderBy("tipo", 'asc')
                        ->orderBy("dia", 'asc')
                        ->orderBy("hora", 'asc');
                    }])
                ->first();

        foreach ($rueda->generada as $i => $viaje) {
            $tmp = json_decode($rueda->generada[$i]->coches);
            $rueda->generada[$i]->coches = $tmp;
        }

        return response()->json($rueda, 200);
    }

    public function generateRueda($id = null)
    {
        $rueda = Rueda::with("viajes")->first();
        $semana = $this->recuperarDatos($rueda);
        $valido = false;

        //Intenta generar la rueda
        $condiciones=[
            "noUsar"=>[]
        ];
        $intentos = 0;
        do {
            $response = $this->asignarCoches($semana,$condiciones);
            $generada = $response['data'];
            if($response['status']==0) {
                $valido = true;
            } else {
                $condiciones = $response['extra'];
            }
            $intentos++;
        } while (!$valido && $intentos<10);
        if($valido && isset($generada)){
            $this->guardarRuedaGenerada($id,$generada);
        }
//        Muestra los datos generados para pruebas
//        $this->pintarTabla($generada);
    }

    /**
     * Recupera los viajeros de una rueda
     * @param $rueda
     * @return array
     */
    private function recuperarDatos($rueda)
    {
        $semana = [];
        // Recupera los datos de usuarios por dia y hora
        foreach ($rueda->viajes as $viaje) {
            if (!isset($semana[$viaje->dia])) $semana[$viaje->dia] = [
                "ida" => [
                    "totalcoches" => 0,
                    "horas" => []
                ],
                "vuelta" => [
                    "totalcoches" => 0,
                    "horas" => []
                ],
            ];
            // Recupera los usuarios de ese viaje
            $viajeros = Rueda_viajes_usuario::with('usuario')->where('id_rueda_viaje', '=', $viaje->id)->get();
            $cuantosCoches = intdiv(count($viajeros), 4) + (count($viajeros) % 4 == 0 ? 0 : 1);
            //Cuenta los coches que se necesitarán para esa hora y los suma al total de coches para la ida y la vuelta
            $semana[$viaje->dia][$viaje->tipo == 1 ? "ida" : "vuelta"]["horas"][$viaje->hora] = [
                "viajeros" => [],
                "conductores"=>[],
                "coches" => array_fill(0, $cuantosCoches, [
                    "conductor" => null,
                    "pasajeros" => [],
                ])
            ];
            $semana[$viaje->dia][$viaje->tipo == 1 ? "ida" : "vuelta"]["totalcoches"] += $cuantosCoches;
            foreach ($viajeros as $viajero) {
                $semana[$viaje->dia][$viaje->tipo == 1 ? "ida" : "vuelta"]["horas"][$viaje->hora]["viajeros"][$viajero->usuario->id] = $viajero->usuario;
            }
            if(count($viajeros)==1){
                $semana[$viaje->dia][$viaje->tipo == 1 ? "ida" : "vuelta"]["horas"][$viaje->hora]["coches"][0]["conductor"]=$viajeros[0]->usuario->id;
            }
        }
        foreach ($semana as $diaN => $viajes) {
            //Comprueba que haya los mismos coches de ida que de vuelta
            while ($viajes["ida"]["totalcoches"] != $viajes["vuelta"]["totalcoches"]) {
                $tipo = $viajes["ida"]["totalcoches"] > $viajes["vuelta"]["totalcoches"] ? "vuelta" : "ida";
                //Comprueba en que hora hay mas viajeros por coche para repartirlos en mas coches
                $max = 0;
                $repartir = null;
                //Comprueba en que hora hay más viajeros por coche y los pone en un coche mas
                foreach ($viajes[$tipo]["horas"] as $hora => $viaje) {
                    if (count($viaje['viajeros']) / count($viaje['coches']) > $max) {
                        $max = count($viaje['viajeros']) / count($viaje['coches']);
                        $repartir = $hora;
                    } elseif (count($viaje['viajeros']) / count($viaje['coches']) == $max) {
                        if (count($viaje['viajeros']) > count($viajes[$tipo]["horas"][$repartir])) {
                            $max = count($viaje['viajeros']) / count($viaje['coches']);
                            $repartir = $hora;
                        }
                    }
                }
                $viajes[$tipo]["horas"][$repartir]["coches"][] = [
                    "conductor" => null,
                    "pasajeros" => [],
                ];
                $viajes[$tipo]["totalcoches"]++;
            }
            $semana[$diaN] = $viajes;
        }
        return $semana;
    }

    /**
     * @param $semana
     * @param array $condiciones
     * @return array
     */
    private function asignarCoches($semana,$condiciones=[])
    {
        $status=0;
        $haConducido=[];
        foreach ($semana as $diaN => $viajes) {

            $esConductor = [];
            $horasIda = array_keys($viajes["ida"]["horas"]);
            for ($pos = 0; $pos < count($horasIda); $pos++) {
                $hora = $horasIda[$pos];
                $datos = $viajes["ida"]["horas"][$hora];
                $idviajeros = array_keys($datos["viajeros"]);
                foreach ($datos["coches"] as $keyCoche => $coche) {
                    $seguir = false;
                    for ($i = 0; !$seguir && $i < count($idviajeros); $i++) {
                        $id = $idviajeros[$i];
                        $saltar=false;
                        /// BLOQUE DE CONDICIONES
                        if(isset($condiciones["noUsar"][$diaN])){
                            if(in_array($id,$condiciones["noUsar"][$diaN]))$saltar=true;
                        }
                        if(in_array($id, $esConductor))$saltar=true;
                        if (!$saltar) {
                            $response = $this->asignarConductorVuelta($semana[$diaN]["vuelta"], $id,count($idviajeros)==1);
                            if($response === true){
                                $esConductor[] = $id;
                                $semana[$diaN]["ida"]["horas"][$hora]["coches"][$keyCoche]["conductor"] = $id;
                                if(!in_array($id,$semana[$diaN]["ida"]["horas"][$hora]))
                                    $semana[$diaN]["ida"]["horas"][$hora]["conductores"][]=$id;
                                $seguir = true;
                            } elseif($response !== false){
                                if(array_key_exists($diaN,$condiciones["noUsar"]) && !in_array($response,$condiciones["noUsar"][$diaN]))
                                    $condiciones["noUsar"][$diaN][]=$response;
                                else $condiciones["noUsar"][$diaN]=[$response];
                                return [
                                    "status"=>1,
                                    "data"=>$semana,
                                    "extra" => $condiciones
                                ];
                            }
                        }
                    }
                    if($coche["conductor"]==null){
                        for ($i = 0; !$seguir && $i < count($idviajeros); $i++) {
                            $id = $idviajeros[$i];
                            $saltar=in_array($id, $esConductor);
                            if(!$saltar && isset($condiciones["noUsar"][$diaN])){
                                $saltar=in_array($id,$condiciones["noUsar"][$diaN]);
                            }
                            if (!$saltar) {
                                foreach ($viajes["vuelta"]["horas"] as $keyViaje => $viaje) {
                                    $ids = array_keys($viaje['viajeros']);
                                    if (in_array($id, $ids)) {
                                        foreach ($ids as $idViajero){
                                            if(in_array($idViajero,$esConductor)){
                                                if (isset($condiciones["noUsar"][$diaN]) && !in_array($response, $condiciones["noUsar"][$diaN])) $condiciones["noUsar"][$diaN][] = $idViajero;
                                                else $condiciones["noUsar"][$diaN] = [$idViajero];
                                                return [
                                                    "status"=>1,
                                                    "data"=>$semana,
                                                    "extra" => $condiciones
                                                ];

                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                $i=0;
                foreach ($datos["viajeros"] as $idViajero=>$viajero) {
                    if(!in_array($idViajero,$esConductor)){
                        $semana[$diaN]["ida"]["horas"][$hora]["coches"][$i]["pasajeros"][]=$idViajero;
                        $i++;
                        if($i>=count($datos["coches"]))$i=0;
                    }
                }
            }
            foreach ($viajes["vuelta"]["horas"] as $hora=>$datos) {
                $i=0;
                foreach ($datos["viajeros"] as $idViajero=>$viajero) {
                    if(!in_array($idViajero,$esConductor)){
                        $semana[$diaN]["vuelta"]["horas"][$hora]["coches"][$i]["pasajeros"][]=$idViajero;
                        $i++;
                        if($i>=count($datos["coches"]))$i=0;
                    }
                }
            }
        }
        return [
            "status"=>$status,
            "data"=>$semana,
            "extra"=>null,
        ];
    }

    /**
     * @param array $viajes
     * @param null $id
     * @param false $obligado => Condiciones del usuario
     * @return bool|mixed => Devuelve el id de un usuario si no puede ser conductor
     */
    private function asignarConductorVuelta(&$viajes = [], $id = null,$obligado=false)
    {
        if ($id == null) return false;
        foreach ($viajes["horas"] as $keyViaje => $viaje) {
            if (array_key_exists($id, $viaje['viajeros'])) {
                foreach ($viaje["coches"] as $keyCoche => $coche) {
                    if ($coche['conductor'] == null || $coche['conductor'] === $id) {
                        $viajes["horas"][$keyViaje]["coches"][$keyCoche]['conductor'] = $id;
                        if(!in_array($id,$viajes["horas"][$keyViaje]["conductores"]))
                            $viajes["horas"][$keyViaje]["conductores"][]=$id;
                        return true;
                    }
                }
                if($obligado){
                    $devolver = $viajes["horas"][$keyViaje]["coches"][0]['conductor'];
                    $viajes["horas"][$keyViaje]["coches"][0]['conductor']=$id;
                    return $devolver;
                }
            }
        }
        return false;
    }

    /**
     * @param int $idRueda
     * @param array $rueda Datos a almacenar
     */
    private function guardarRuedaGenerada($idRueda=0,$rueda=[]){
        function guardarViaje($idRueda,$dia,$tipo,$viajes){
            foreach ($viajes["horas"] as $hora=>$viaje) {
                foreach ($viaje["coches"] as $keyCoche=>$coche) {
                    $idConductor = $coche["conductor"];
                    $viaje["coches"][$keyCoche]["conductor"]=($viaje["viajeros"][$idConductor]->name." ".$viaje["viajeros"][$idConductor]->surname);
                    foreach ($coche["pasajeros"] as $key=>$pasajero) {
                        $viaje["coches"][$keyCoche]["pasajeros"][$key]=($viaje["viajeros"][$pasajero]->name." ".$viaje["viajeros"][$pasajero]->surname);
                    }
                }
                RuedaGenerada::create([
                    "idRueda"=>$idRueda,
                    "dia"=>$dia,
                    "hora"=>$hora,
                    "tipo"=>$tipo,
                    "coches"=>json_encode($viaje["coches"])
                ]);
            }
        }
        RuedaGenerada::where("idRueda",$idRueda)->delete();
        foreach ($rueda as $dia => $viajes) {
            guardarViaje($idRueda,$dia,1,$viajes["ida"]);
            guardarViaje($idRueda,$dia,2,$viajes["vuelta"]);
        }
    }

    /**
     * @param $semana
     */
    private function pintarTabla($semana)
    {
        echo "<table style='border: 1px solid;width: 100%;border-collapse: collapse;'>
                <tbody style='display: flex;'>";
        foreach ($semana as $dia => $viajes) {
            echo "<tr style='display: flex; flex-direction: column; flex: 1;'>";
            echo "<th style='border: 1px solid;'>$dia</th>";
            foreach ($viajes['ida']["horas"] as $viaje) {
//                    dd($viaje);
                $this->pintarCeldas($viaje);
            }
            echo "<td style='border-width: 3px 0px; border-style: solid;'></td>";
            foreach ($viajes['vuelta']["horas"] as $viaje) {
//                    dd($viaje);
                $this->pintarCeldas($viaje);
            }
            echo "</tr>";
        }
        echo "</tbody></table>";

    }

    private function pintarCeldas($viaje)
    {
        echo "<td style='border: 1px solid; height: 100px;overflow: auto;display: flex;'>";
        foreach ($viaje["coches"] as $i => $coche) {
            echo "<div style='flex: 1;padding: 1px;'>";
            if (isset($viaje['viajeros'][$coche['conductor']])) {
                echo "<b>" . $viaje['viajeros'][$coche['conductor']]->name . " " . $viaje['viajeros'][$coche['conductor']]->surname . "</b>";
            }
            echo "<ul>";
            foreach ($coche['pasajeros'] as $id => $viajero) {
                echo "<li>".$viaje['viajeros'][$viajero]->name . " " . $viaje['viajeros'][$viajero]->surname ."</li>";
            }
            echo "</ul>";
//                        dd($coche);
            echo "</div>";
        }
        echo "</td>";

    }


    /**
     * @param array $viajeros => usuario a repartir en coches
     * @param array $esConductor => lista de usuarios que conducen
     * @return array
     * @var integer $max => numero máximo de ocupantes por coche
     */
    private function repartirViajesIda($viajeros = [], &$esConductor = [], $condiciones = null)
    {
        $cont = 0;
        $max = 4;
        $coches = [];
        foreach ($viajeros as $key => $viajero) {
            if ($cont == 0) {
                $esConductor[] = $key;
                $conductor = $viajero->name . " " . $viajero->surname;
                $pasajeros = [];
            } else {
                $pasajeros[] = $viajero->name . " " . $viajero->surname;;
            }
            $cont++;
            if ($cont == $max) {
                $coches[] = [
                    "conductor" => $conductor,
                    "pasajeros" => join(",", $pasajeros)
                ];
                $conductor = null;
                $cont = 0;
            }
        }
        if ($conductor != null) {
            $coches[] = [
                "conductor" => $conductor,
                "pasajeros" => join(",", $pasajeros)
            ];
        }
        return $coches;
    }

    /**
     * @param array $viajeros
     * @param array $conductores
     * @var integer $max =>  numero maximo de ocupantes por coche
     */
    private function repartirViajesVuelta($viajeros = [], &$conductores = [])
    {
        $cont = 0;
        $max = 4;
        $cuantos = count($viajeros);
        $coches = [];
        //Comprueba los viajeros que han sido conductores a la ida
        for ($i = 0; $i < intdiv($cuantos, $max); $i++) {
            $coches[] = [
                "conductor" => null,
                "pasajeros" => []
            ];
        }
        if ($cuantos % $max !== 0) {
            $coches[] = [
                "conductor" => null,
                "pasajeros" => []
            ];
        }
        //Reparte los viajeros en los coches
        foreach ($viajeros as $viajero) {
            if (in_array($viajero->id, $conductores)) {
                $i = 0;
                $puesto = false;
                while ($i < count($coches) && !$puesto) {
                    if ($coches[$i]["conductor"] === null) {
                        $coches[$i]["conductor"] = $viajero->name . " " . $viajero->surname;
                        $puesto = true;
                    }
                    $i++;
                }
                if ($puesto == false) {
                    return false;
                }
            } else {
                $i = 0;
                $puesto = false;
                while ($i < count($coches) && !$puesto) {
                    if (count($coches[$i]["pasajeros"]) < 4) {
                        $coches[$i]["pasajeros"][] = $viajero->name . " " . $viajero->surname;
                        $puesto = true;
                    }
                    $i++;
                }
                if (!$puesto) {
                    return false;
                }
            }
        }
        return $coches;
    }
}
