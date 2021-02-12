<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rueda;
use App\Models\Rueda_viajes_usuario;
use App\Models\RuedaGenerada;
use Illuminate\Http\Request;

class Ruedas extends Controller {
    private $condiciones=[
        "max"=>1,
        "dias"=>[],
        "haConducido"=>[],
    ];
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
        //Recupera los datos de la rueda
        $rueda = Rueda::with("viajes")->first();
        // Recupera los datos asociados de la rueda con los viajeros
        // Calcula el numero de coches necesarios
        $datos = $this->recuperarDatos($rueda);
        $semana = $datos["semana"];
        $horas = $datos["horas"];
        $dias = $datos["dias"];
        $this->generarCuadrante($semana,$dias,$horas);
        dd(54,$semana);
    }

    /**
     * Recupera los viajeros de una rueda
     * @param $rueda
     * @return array
     */
    private function recuperarDatos($rueda)
    {
        $semana = [];
        $horas = null;
        $dias = [];
        // Recupera los datos de usuarios por dia y hora
        foreach ($rueda->viajes as $viaje) {
            if (!isset($semana[$viaje->dia])) {
                $semana[$viaje->dia] = [
                    "ida" => [
                        "totalcoches" => 0,
                        "horas" => []
                    ],
                    "vuelta" => [
                        "totalcoches" => 0,
                        "horas" => []
                    ],
                ];
            }
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
        }
        $dias = array_keys($semana);
        foreach ($semana as $diaN => $viajes) {
            //Recupera las horas
            if($horas === null)$horas=array_keys($viajes["ida"]["horas"]);

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
        return [
            "semana"=>$semana,
            "dias"=>$dias,
            "horas"=>$horas,
        ];
    }


    private function generarCuadrante(&$cuadrante,$dias,$horas,$dia=0,$hora=0,$posicion=0){
        $exito = false;
        $esImposible=false;
        do {
            // Comprueba si se han asignado todos los conductores
            if ($this->solucionCompleta($dias,$dia)) {
                $exito = true;
            } else {
                try {
                    // Recupera los datos del conductor a comprobar
                    $conductor = $this->getViajero($cuadrante[$dias[$dia]]["ida"]["horas"][$horas[$hora]]["viajeros"],$posicion);
                    if(!$conductor)throw new Exception("Error Processing Request", 1);

                } catch (\Throwable $th) {
                    dd(154,count($cuadrante[$dias[$dia]]["ida"]["horas"][$horas[$hora]]["viajeros"]),$dias[$dia],$horas[$hora],$posicion,$cuadrante[$dias[$dia]]);
                }
                echo "<br>$dias[$dia]  <===> $horas[$hora]  ## $posicion  || $conductor->id ";
                flush();
                if ($this->puedeSerConductor($cuadrante, $horas[$hora], $dias[$dia], $conductor)) {

                    $this->asignarConductor($cuadrante, $horas[$hora], $dias[$dia], $conductor->id);
                    //Si en ese día ya tenemos todos los conductores... pasamos al siguiente día.
                    if($this->estaDiaCubierto($cuadrante[$dias[$dia]])){
                        $exito = $this->generarCuadrante($cuadrante,$dias,$horas,$dia+1,0,0);
                        if(!$exito){
                            $this->cancelarConductor($cuadrante[$dias[$dia]],$conductor->id);
                            if(isset($this->condiciones["exluir"]))$this->condiciones["excluir"][]=$conductor->id;
                            else $this->condiciones["excluir"]=[$conductor->id];
                        }

                        //Faltan todos los coches están cubiertos pasa a la siguiente hora
                    } elseif ($this->estaHoraCubierta($cuadrante[$dias[$dia]]["ida"]["horas"][$horas[$hora]])) {
                        dd($cuadrante[$dias[$dia]]["ida"]["horas"][$horas[$hora]]);
                        $exito = $this->generarCuadrante($cuadrante,$dias,$horas,$dia,$hora+1,0);
                        if(!$exito){
                            $this->cancelarConductor($cuadrante[$dias[$dia]],$conductor->id);
                        }
                    }
                } else { //Si no se puede ser conductor...
                    //Comprueba si quedan viajeros para probar como conductor
                    if($this->quedanViajeros($posicion,$cuadrante[$dias[$dia]]["ida"]["horas"][$horas[$hora]])){
                        //Pasamos al siguiente conductor de los disponibles en esa franja.
                        $exito = $this->generarCuadrante($cuadrante,$dias,$horas,$dia,$hora,$posicion+1);
                        if(!$exito && !$this->quedanViajeros($posicion+1,$cuadrante[$dias[$dia]]["ida"]["horas"][$horas[$hora]])){
                            $this->cancelarConductor($cuadrante[$dias[$dia]],$conductor->id);
                        }
                    } else {//Si no quedan posibles conductores es imposible la combinación
                        $esImposible = true;
                    }
                }
            }
        } while (!$exito && !$esImposible);

        return $exito;
    }

    private function solucionCompleta($dias,$dia){
        return $dia >= count($dias);
    }
    /**
     * Recupera el usuario de una posición
     */
    private function getViajero($viajeros=[],$pos=0){
        $ids = array_keys($viajeros);
        return isset($ids[$pos])?$viajeros[$ids[$pos]]:false;
    }
    /** */
    private function puedeSerConductor($cuadrante, $hora, $dia, $conductor){
        $id = $conductor->id;
        $viajes = $cuadrante[$dia]["vuelta"]["horas"];
        $response = $this->puedeSerConductorVuelta($viajes, $conductor->id,count($cuadrante[$dia]["ida"]["horas"][$hora]["viajeros"])==1);
        // if(is_bool($response)){
        //     return $response;
        // } else {
        //     dd($response);
        //     return false;
        // }
        return $response;
        // if($response === true){
        //     $esConductor[] = $id;
        //     $semana[$diaN]["ida"]["horas"][$hora]["coches"][$keyCoche]["conductor"] = $id;
        //     if(!in_array($id,$semana[$diaN]["ida"]["horas"][$hora]))
        //         $semana[$diaN]["ida"]["horas"][$hora]["conductores"][]=$id;
        //     $seguir = true;
        // } elseif($response !== false){
        //     if(array_key_exists($diaN,$condiciones["noUsar"]) && !in_array($response,$condiciones["noUsar"][$diaN]))
        //         $condiciones["noUsar"][$diaN][]=$response;
        //     else $condiciones["noUsar"][$diaN]=[$response];
        //     return [
        //         "status"=>1,
        //         "data"=>$semana,
        //         "extra" => $condiciones
        //     ];
        // }
    }
    /**
     *
     */
    private function puedeSerConductorVuelta($viajes, $id){
        foreach ($viajes as $keyViaje => $viaje) {
            if (array_key_exists($id, $viaje['viajeros'])) {
                foreach ($viaje["coches"] as $keyCoche => $coche) {
                    if ($coche['conductor'] == null) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
    /**
     * Comprueba si todos los coches de una hora están cubiertos
     */
    private function estaHoraCubierta($hora){
        return count($hora["coches"]) == count($hora["conductores"]);
    }
    /**
     * Comprueba que todos los coches de las horas de un día están cubiertos
     */
    private function estaDiaCubierto($dia){
        foreach ($dia["ida"]["horas"] as $hora) {
            if(!$this->estaHoraCubierta($hora))return false;
        }
        foreach ($dia["vuelta"]["horas"] as $hora) {
            if(!$this->estaHoraCubierta($hora))return false;
        }
        return true;
    }
    /**
     * Asigna un conductor a la ida y vuelta
     * @return bool
     */
    private function asignarConductor(&$cuadrante, $horaIda, $dia, $id){
        $correcto1 = false;
        $correcto2 = false;
        // Asigna a la ida
        for ($key=0; !$correcto1 && $key < count($cuadrante[$dia]["ida"]["horas"][$horaIda]["coches"]); $key++) {
            $coche=$cuadrante[$dia]["ida"]["horas"][$horaIda]["coches"][$key];
            if($coche["conductor"]==null || $coche["conductor"] === $id){
                $cuadrante[$dia]["ida"]["horas"][$horaIda]["coches"][$key]["conductor"]=$id;
                $cuadrante[$dia]["ida"]["horas"][$horaIda]["conductores"][]=$id;
                $correcto1 = true;
            }
        }
        // Asigna a la vuelta
        $horasVuelta = array_keys($cuadrante[$dia]["vuelta"]["horas"]);
        for($i=0;!$correcto2 && $i<count($horasVuelta); $i++){
            $horaVuelta = $horasVuelta[$i];
            $viaje = $cuadrante[$dia]["vuelta"]["horas"][$horaVuelta];
            if(array_key_exists($id,$viaje["viajeros"])){
                for ($key=0; !$correcto2 && $key < count($viaje["coches"]); $key++) {
                    $coche = $viaje["coches"][$key];
                    if($coche["conductor"]==null || $coche["conductor"] === $id){
                        $cuadrante[$dia]["vuelta"]["horas"][$horaVuelta]["coches"][$key]["conductor"]=$id;
                        $cuadrante[$dia]["vuelta"]["horas"][$horaVuelta]["conductores"][]=$id;
                        $correcto2 = true;
                    }
                }
            }
        }
        return $correcto1 && $correcto2;
    }

    private function cancelarConductor(&$dia,$conductor){
        foreach ($dia["ida"]["horas"] as $hora => $viaje) {
            // dd($conductor,$viaje["viajeros"],array_key_exists($conductor,$viaje["viajeros"]));
            if(array_key_exists($conductor,$viaje["viajeros"])){
                $dia["ida"]["horas"][$hora]["conductores"]=array_diff($viaje["conductores"],[$conductor]);
                foreach ($viaje["coches"] as $key => $coche) {
                    if($coche["conductor"] == $conductor){
                        $dia["ida"]["horas"][$hora]["coches"][$key]=null;
                    }
                }
            }
        }
        foreach ($dia["vuelta"]["horas"] as $hora => $viaje) {
            if(array_key_exists($conductor,$viaje["viajeros"])){
                $dia["vuelta"]["horas"][$hora]["conductores"]=array_diff($viaje["conductores"],[$conductor]);
                foreach ($viaje["coches"] as $key => $coche) {
                    if($coche["conductor"] == $conductor){
                        $dia["vuelta"]["horas"][$hora]["coches"][$key]=null;
                    }
                }
            }
        }
    }

    /**
     *
     */
    private function cancelarConductores(&$cuadrante,$dia,$hora){
        foreach ($cuadrante[$dia]["ida"]["horas"][$hora]["coches"] as $key => $coche) {
            $cuadrante[$dia]["ida"]["horas"][$hora]["coches"][$key]["conductor"]=null;
        }
        foreach ($cuadrante[$dia]["ida"]["horas"][$hora]["conductores"] as $conductor) {
            // Asigna a la vuelta
            $eliminado = false;
            $horasVuelta = array_keys($cuadrante[$dia]["vuelta"]["horas"]);
            for($i=0;!$eliminado && $i<count($horasVuelta); $i++){
                $horaVuelta = $horasVuelta[$i];
                $viaje = $cuadrante[$dia]["vuelta"]["horas"][$horaVuelta];
                if(array_key_exists($conductor,$viaje["viajeros"])){
                    for ($key=0; !$eliminado && $key < count($viaje["coches"]); $key++) {
                        $coche = $viaje["coches"][$key];
                        if($coche["conductor"]==null || $coche["conductor"] === $conductor){
                            $cuadrante[$dia]["vuelta"]["horas"][$horaVuelta]["coches"][$key]["conductor"]=null;
                            $eliminado = true;
                        }
                    }
                    $pos = array_search($conductor,$viaje["conductores"]);
                    unset($cuadrante[$dia]["vuelta"]["horas"][$horaVuelta]["conductores"][$pos]);
                }
            }
        }
        $cuadrante[$dia]["ida"]["horas"][$hora]["conductores"]=[];
    }

    private function quedanViajeros($posicion,$hora){
        return $posicion+1<count($hora["viajeros"]);
    }






    ////////////////////////////////////////////////////////////
    //   ANTERIOR
    public function anterior(){
        $valido = false;

        //Intenta generar la rueda
        $condiciones=[
            "noUsar"=>[]
        ];
        $intentos = 0;
        do {
            $response = $this->asignarCoches($semana);
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
                    "id_rueda"=>$idRueda,
                    "dia"=>$dia,
                    "hora"=>$hora,
                    "tipo"=>$tipo,
                    "coches"=>json_encode($viaje["coches"])
                ]);
            }
        }
        RuedaGenerada::where("id_rueda",$idRueda)->delete();
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
