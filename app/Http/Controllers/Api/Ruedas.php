<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rueda;
use App\Models\Rueda_salidas;
use App\Models\Rueda_viajes_usuario;
use App\Models\RuedaGenerada;
use App\Models\User;
use Illuminate\Http\Request;
use App\Mail\RuedaRehecha;
use Illuminate\Support\Facades\Mail;

class Ruedas extends Controller {
    private $condiciones=[
        "max"=>1,
        "dias"=>[],
        "conductores"=>[],
        "viajeros"=>[],
    ];
    /**
     * Recupera todas las ruedas
     */
    public function getAll() {
        $ruedas = Rueda::with('salidas')->get();
        return response()->json($ruedas,200);
    }

    /**
     * Recupera los datos básicos y los viajes de una rueda
     * @param Integer $id Identificador de la rueda
     */
    public function getRueda($id = null)
    {
        $rueda = $this->getRuedaDB($id);
        return response()->json($rueda, 200);
    }
    /**
     * Recupera los datos de la base de datos
     */
    public function getRuedaDB($id=null){
        $rueda = Rueda::with("viajes","salidas")->where("id",$id)->first();
        return $rueda;
    }
    /**
     * Recupera los datos básicos y viajes con los viajeros asociados de una rueda
     * @param Integer $id Identificador de la rueda
     */
    public function getRuedaGenerada($id = null) {
        $rueda = Rueda::with(["generada" => function($result) {
                        $result->orderBy("tipo", 'asc')
                        ->orderBy("dia", 'asc')
                        ->orderBy("hora", 'asc');
                    }])
                ->where("id",$id)
                ->first();

        foreach ($rueda->generada as $i => $viaje) {
            $coches = json_decode($rueda->generada[$i]->coches);
            foreach ($coches as $coche) {
                $user = User::where("id",$coche->conductor)->first();
                if($user){
                    $coche->conductor = $user->name." ".$user->surname;
                } else {
                    $coche->conductor = "";
                }
                foreach ($coche->pasajeros as $key=>$pasajero) {
                    $user = User::where("id",$pasajero)->first();
                    if($user){
                        $coche->pasajeros[$key] = $user->name." ".$user->surname;
                    } else {
                        $coche->pasajeros[$key] = "";
                    }
                }
                $salida = Rueda_salidas::where("id",$coche->salida)->first();
                $coche->salida = $salida->nombre;
            }
            $rueda->generada[$i]->coches = $coches;
        }

        return response()->json($rueda, 200);
    }
    /**
     * Crea la asignación de conductores y pasajeros de una rueda.
     * @param Integer $id Identificador de la rueda
     */
    public function generateRueda($id = null)
    {
        set_time_limit(120);
        //Recupera los datos de la rueda
        $rueda = Rueda::with("viajes")->where("id",$id)->first();
        // Recupera los datos asociados de la rueda con los viajeros
        // Calcula el numero de coches necesarios
        $datos = $this->recuperarDatos($rueda);
        $semana = $datos["semana"];
        $horas = $datos["horas"];
        $dias = $datos["dias"];
        $this->condiciones["conductores"]=[];
        //Asigna los conductores y pasajeros
        $exito;
        //Si no se consigue con las condiciones de conducción maxima se aumenta en una
        $p=0;
        $coches=0;
        if($datos["haycoincidencia"]==0){
            $this->condiciones['max']=count($dias);
        }
        do{
            $exito = $this->generarCuadrante($semana,$dias,$horas,0,0,$p);
            if(!$exito){
                //Prueba el siguiente viajero de la primera hora
                if($p < count($semana[$dias[0]]["ida"]["horas"][$horas[0]]["viajeros"])){
                    $p++;
                } elseif($this->condiciones["max"]<=5) {
                    $p=0;
                    $this->condiciones["max"]++;
                //Agrega un nuevo coche
                } elseif($coches<count($this->condiciones["viajeros"])) {
                    $coches++;
                    $this->agregarCocheSemana($semana);
                    $this->condiciones["max"]=1;
                    $p=0;
                }
                // $p++;
            }
        }while(!$exito);
        // Almacena la tabla generada
        if($exito)$this->guardarRuedaGenerada($id,$semana);
        return $exito;
    }
    /**
     * Comprueba la densidad de usuarios/coche y agrega un coche en esa
     */
    public function agregarCocheSemana(&$semana){
        $diaCambiar=null;
        $horaCambiar=null;
        $max=0;
        $avg=0;
        foreach($semana AS $diaKey=>$dia){
            foreach ($dia["ida"]["horas"] as $hora => $viaje) {
                if(count($viaje['viajeros']) ==  count($viaje['coches'])) continue;
                if(count($viaje['viajeros']) == 0)continue;
                try {
                    $avg = count($viaje['viajeros']) / count($viaje['coches']);
                } catch (\Throwable $th) {
                    $avg = 1;
                }
                if ($avg > $max) {
                    $max = $avg;
                    $diaCambiar = $diaKey;
                    $horaCambiar = $hora;
                } elseif ($avg == $max) {
                    if (count($viaje['viajeros']) > count($semana[$diaCambiar]["ida"]["horas"][$horaCambiar]["viajeros"])) {
                        $max = $avg;
                        $diaCambiar = $diaKey;
                        $horaCambiar = $hora;
                    }
                }
            }
        }
        if($horaCambiar==null){
            dd($semana);
        }
        $this->agregarCoche($semana[$diaCambiar]["ida"],$horaCambiar);
        $horaCambiar=null;
        $max=0;
        $avg=0;
        foreach ($semana[$diaCambiar]["vuelta"]["horas"] as $hora => $viaje) {
            try {
                $avg = count($viaje['viajeros']) / count($viaje['coches']);
            } catch (\Throwable $th) {
                $avg = 0;
            }
            if ($avg > $max) {
                $max = $avg;
                $horaCambiar = $hora;
            } elseif ($avg == $max) {
                if (count($viaje['viajeros']) > count($semana[$diaCambiar]["vuelta"]["horas"][$horaCambiar?:$hora]["viajeros"])) {
                    $max = $avg;
                    $horaCambiar = $hora;
                }
            }
        }
        if($horaCambiar==null){
            dd($avg);
        }
        $this->agregarCoche($semana[$diaCambiar]["vuelta"],$horaCambiar);
    }

    /**
     * Recupera los viajeros de una rueda
     * @param $rueda
     * @return array
     */
    public function recuperarDatos($rueda)
    {
        $semana = [];
        $horas = null;
        $hayCoincidencia=0;
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
            $usuarios = Rueda_viajes_usuario::with('usuario')->where('id_rueda_viaje', $viaje->id)->get();
            $viajeros=[];
            foreach ($usuarios as $viajero) {
                if(!isset($viajero->usuario)){
                    continue;
                }
                // Recupera las reglas de un viajero para un viaje
                if(empty($viajero->reglas)){
                    $viajero->usuario->reglas=[
                        "irSolo" => 0,
                        "plazas" => 4,
                        "salida" => 1,
                    ];
                } else {
                    $reglas = json_decode($viajero->reglas);
                    $viajero->usuario->reglas=$reglas;
                    if(isset($viajero->usuario->reglas->irSolo) && $viajero->usuario->reglas->irSolo == 1)$viajero->usuario->reglas->plazas=0;
                }
                if(!in_array($viajero->usuario->id,$this->condiciones["viajeros"]))$this->condiciones["viajeros"][]=$viajero->usuario->id;
               $viajeros[$viajero->usuario->id] = $viajero->usuario;
            }
            $cuantosCoches = intdiv(count($viajeros), 4);
            if(count($viajeros)%4!=0)$cuantosCoches+=1;

            //Cuenta los coches que se necesitarán para esa hora y los suma al total de coches para la ida y la vuelta
            $semana[$viaje->dia][$viaje->tipo == 1 ? "ida" : "vuelta"]["horas"][$viaje->hora] = [
                "viajeros" => $viajeros,
                "conductores"=>[],
                "coches" => array_fill(
                    0, $cuantosCoches, [
                    "conductor" => null,
                    "pasajeros" => [],
                    "salida" => null,
                    "plazas" => 0,
                ])
            ];;

            $semana[$viaje->dia][$viaje->tipo == 1 ? "ida" : "vuelta"]["totalcoches"] += $cuantosCoches;
            if(count($viajeros)>1){
                $hayCoincidencia++;
            }
        }
        $this->ajustarCoches($semana,$horas);
        return [
            "semana"=>$semana,
            "dias"=>array_keys($semana),
            "horas"=>$horas,
            "haycoincidencia"=>$hayCoincidencia
        ];
    }
    /**
     * Comprueba que la cantidad de coches al ir y al volver sea igual
     * * @param Array $semana rueda a generar
     */
    public function ajustarCoches(&$semana,&$horas){
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
                    try {
                        $avg = count($viaje['viajeros']) / count($viaje['coches']);
                    } catch (\Throwable $th) {
                        $avg = 0;
                    }
                    if ($avg > $max) {
                        $max = $avg;
                        $repartir = $hora;
                    } elseif ($avg == $max) {
                        if(!isset($viaje["viajeros"])){
                            dd($viaje);
                        }
                        if (count($viaje['viajeros']) > count($viajes[$tipo]["horas"][$repartir?:$hora])) {
                            $max = $avg;
                            $repartir = $hora;
                        }
                    }
                }
                $this->agregarCoche($viajes[$tipo],$repartir);
            }
            $semana[$diaN] = $viajes;
        }
    }

    public function agregarCoche(&$dia,$hora) {
        if(!isset($dia["totalcoches"]))
            dd($dia);
        $dia["horas"][$hora]["coches"][] = [
            "conductor" => null,
            "pasajeros" => [],
            "salida" => null,
            "plazas" => 0,
        ];
        $dia["totalcoches"]++;
        // if($hora!=="08:30" && $hora!=="12:40" && $hora!=="10:20");
        //     dd($dia,$hora);
    }
    /**
     * Función para sacar texto por pantall durante la ejecución del código
     */
    public function output($texto="",$empiezaLinea=false){
        if($empiezaLinea)echo "<br>";
        echo $texto;
        flush();
    }
    /**
     * Asigna un viajero como conductor un dia a una hora y en su vuelta
     * @param Object $cuadrante Rueda que contiene los viajes
     * @param Array $dias Lista de días que hay en $cuadrante
     * @param Array $horas Lista de horas asignadas para los viajes de ida
     * @param Integer $dia Posición del día dentro de $dias que se está comprobando
     * @param Integer $horas Posición de la hora dentro de $horas que se está comprobando
     * @param Integer $posicion Posición del viajero que se está comprobando dentro de la lista de una hora
     */
    public function generarCuadrante(&$cuadrante,$dias,$horas,$dia=0,$hora=0,$pos=0){
        $exito = false;
        $esImposible=false;
        $posicion = $pos;
        do {
            // Comprueba si se han asignado todos los conductores
            if ($this->solucionCompleta($dias,$dia)) {
                // Comprueba que todos han conducido
                sort($this->condiciones["viajeros"]);
                $exito = true;
                if(count($this->condiciones["conductores"])==count($this->condiciones["viajeros"])){
                    foreach ($this->condiciones["conductores"] as $key=>$conductor) {
                        if($conductor < ($this->condiciones["max"]-1)){
                            return false;
                        }
                    }
                }
            } else {
                try {
                    // Recupera los datos del conductor a comprobar
                    $conductor = $this->getViajero($cuadrante[$dias[$dia]]["ida"]["horas"][$horas[$hora]]["viajeros"],$posicion);
                    // if(!$conductor)throw new Exception("Error Processing Request", 1);

                } catch (\Throwable $th) {
                }
                // Si no hay conductor comprueba si no hay en ese viaje
                if(!$conductor){
                    if(count($cuadrante[$dias[$dia]]["ida"]["horas"][$horas[$hora]]["viajeros"]) == 0){
                        if($this->estaDiaCubierto($horas,$hora)){
                            $exito = $this->generarCuadrante($cuadrante,$dias,$horas,$dia+1,0,0);
                            if($exito){
                                if(!$this->asignarPasajeros($cuadrante[$dias[$dia]])){
                                    return false;
                                }
                            } else {
                                $esImposible=true;
                            }
                        // Si todos los coches están cubiertos pasa a la siguiente hora
                        } else {
                            $exito = $this->generarCuadrante($cuadrante,$dias,$horas,$dia,$hora+1,0);
                            if(!$exito){
                                $esImposible=true;
                            }
                        }

                    }
                }else
                // Comprueba si puede ser conductor
                if ($conductor && $this->puedeSerConductor($cuadrante, $horas[$hora], $dias[$dia], $conductor)) {
                    $this->asignarConductor($cuadrante, $horas[$hora], $dias[$dia], $conductor->id);
                    // Comprueba los coche cubiertos
                    if ($this->estaHoraCubierta($cuadrante[$dias[$dia]]["ida"]["horas"][$horas[$hora]])) {
                        //Si en ese día ya tenemos todos los conductores... pasamos al siguiente día.
                        if($this->estaDiaCubierto($horas,$hora)){
                            $exito = $this->generarCuadrante($cuadrante,$dias,$horas,$dia+1,0,0);
                            if($exito){
                                $this->asignarPasajeros($cuadrante[$dias[$dia]]);
                            } else {
                                $this->cancelarConductor($cuadrante[$dias[$dia]],$conductor->id);
                            }
                        // Si todos los coches están cubiertos pasa a la siguiente hora
                        } else {
                            $exito = $this->generarCuadrante($cuadrante,$dias,$horas,$dia,$hora+1,0);
                            if(!$exito){
                                $this->cancelarConductor($cuadrante[$dias[$dia]],$conductor->id);
                                $esImposible=true;
                            }
                        }

                    } else {
                        // Si quedan viajeros para asignar se intenta
                        if($this->quedanViajeros($posicion,$cuadrante[$dias[$dia]]["ida"]["horas"][$horas[$hora]])) {
                            //Pasamos al siguiente conductor de los disponibles en esa franja.
                            $exito = $this->generarCuadrante($cuadrante,$dias,$horas,$dia,$hora,$posicion+1);
                            if(!$exito){
                                $this->cancelarConductor($cuadrante[$dias[$dia]],$conductor->id);
                            }
                        } else {
                            $this->cancelarConductor($cuadrante[$dias[$dia]],$conductor->id);
                            $esImposible = true;
                        }
                    }
                } else { //Si no se puede ser conductor...
                    //Comprueba si quedan viajeros para probar como conductor
                    if($this->quedanViajeros($posicion,$cuadrante[$dias[$dia]]["ida"]["horas"][$horas[$hora]])){
                        //Pasamos al siguiente conductor de los disponibles en esa franja.
                        $exito = $this->generarCuadrante($cuadrante,$dias,$horas,$dia,$hora,$posicion+1);
                        if(!$exito) $esImposible = true;

                    } else {//Si no quedan posibles conductores es imposible la combinación
                        $esImposible = true;
                    }
                }
            }
            if(!$esImposible && !$exito){
                if($this->quedanViajeros($posicion,$cuadrante[$dias[$dia]]["ida"]["horas"][$horas[$hora]])){
                    $posicion++;
                } else {
                    $esImposible=true;
                }
            }
        } while (!$exito && !$esImposible);

        return $exito;
    }

    public function solucionCompleta($dias,$dia){
        return $dia >= count($dias);
    }
    /**
     * Recupera el usuario de una posición
     */
    public function getViajero($viajeros=[],$pos=0){
        $ids = array_keys($viajeros);
        return isset($ids[$pos])?$viajeros[$ids[$pos]]:false;
    }
    /** */
    public function puedeSerConductor($cuadrante, $hora, $dia, $conductor){
        global $condiciones;
        $id = $conductor->id;
        $viajes = $cuadrante[$dia]["vuelta"]["horas"];
        if(in_array($id,$cuadrante[$dia]["ida"]["horas"][$hora]["conductores"])){
            return false;
        }
        $haConducido = $this->cuantasVecesConduce($id);
        if($haConducido>=$this->condiciones["max"]) return false;
        $response = $this->puedeSerConductorVuelta($viajes, $conductor->id,count($cuadrante[$dia]["ida"]["horas"][$hora]["viajeros"])==1);
        return $response;
    }
    /**
     *
     */
    public function puedeSerConductorVuelta($viajes, $id){
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
     * @arg $conductores array lista de conductores
     *
     */
    public function cuantasVecesConduce($id){
        if(array_key_exists($id,$this->condiciones["conductores"]))return $this->condiciones["conductores"][$id];
        else return 0;
    }
    /**
     * Comprueba si todos los coches de una hora están cubiertos
     */
    public function estaHoraCubierta($hora){
        return count($hora["coches"]) == count($hora["conductores"]);
    }
    /**
     * Comprueba que todos los coches de las horas de un día están cubiertos
     */
    public function estaDiaCubierto($horas,$hora){
        return $hora+1 == count($horas);
        // $cubierto = true;
        // foreach ($dia["ida"]["horas"] as $key=>$hora) {

        //     if(!$this->estaHoraCubierta($hora))$cubierto = false;
        // }
        // foreach ($dia["vuelta"]["horas"] as $key=>$hora) {
        //     if(!$this->estaHoraCubierta($hora))$cubierto = false;
        // }
        // return $cubierto;
    }
    /**
     * Asigna un conductor a la ida y vuelta
     * @return bool
     */
    public function asignarConductor(&$cuadrante, $hora, $dia, $id){
        // Asigna a la ida
        $correcto1 = $this->asignarConductorViaje($cuadrante[$dia]["ida"]["horas"][$hora],$id);
        // Busca el viaje de vuelta en el que está el viajero y lo asigna
        $correcto2 = null;
        $horasVuelta = array_keys($cuadrante[$dia]["vuelta"]["horas"]);
        for($i=0;$correcto2==null && $i<count($horasVuelta); $i++){
            $horaVuelta = $horasVuelta[$i];
            $viaje = $cuadrante[$dia]["vuelta"]["horas"][$horaVuelta];
            if(array_key_exists($id,$viaje["viajeros"])){
                $correcto2 = $this->asignarConductorViaje($cuadrante[$dia]["vuelta"]["horas"][$horaVuelta],$id);
            }
        }
        if(array_key_exists($id,$this->condiciones["conductores"]))$this->condiciones["conductores"][$id]++;
        else $this->condiciones["conductores"][$id]=1;
        return $correcto1 && $correcto2;
    }
    /**
     * Asigna un conductor a un coche de un dia
     * @param Object $viaje Contiene los conductores, viajeros y coches de una hora
     * @param Integer $id Identificador de viajer que se asigna como conductor
     */
    public function asignarConductorViaje(&$viaje,$id){
        $correcto = false;
        for ($key=0; !$correcto && $key < count($viaje["coches"]); $key++) {
            $coche=$viaje["coches"][$key];
            if($coche["conductor"]==null || $coche["conductor"] === $id){
                $viaje["coches"][$key]["conductor"]=$id;
                $viaje["coches"][$key]["salida"]=$viaje["viajeros"][$id]->reglas->salida;
                $viaje["coches"][$key]["plazas"]=$viaje["viajeros"][$id]->reglas->plazas;
                $viaje["conductores"][]=$id;
                $correcto = true;
            }
        }
        return $correcto;
    }
    /**
     * Quita un viajero como conductor
     * @param Object $dia Viaje en el que conduce
     * @param Integer $conductor Identificador del conductor
     */
    public function cancelarConductor(&$dia,$conductor){
        foreach ($dia["ida"]["horas"] as $hora => $viaje) {
            if(array_key_exists($conductor,$viaje["viajeros"])){
                $dia["ida"]["horas"][$hora]["conductores"]=array_diff($viaje["conductores"],[$conductor]);
                foreach ($viaje["coches"] as $key => $coche) {
                    if($coche["conductor"] == $conductor){
                        $dia["ida"]["horas"][$hora]["coches"][$key]["conductor"]=null;
                        $dia["ida"]["horas"][$hora]["coches"][$key]["pasajeros"]=[];
                    }
                }
            }
        }
        foreach ($dia["vuelta"]["horas"] as $hora => $viaje) {
            if(array_key_exists($conductor,$viaje["viajeros"])){
                $dia["vuelta"]["horas"][$hora]["conductores"]=array_diff($viaje["conductores"],[$conductor]);
                foreach ($viaje["coches"] as $key => $coche) {
                    if($coche["conductor"] == $conductor){
                        $dia["vuelta"]["horas"][$hora]["coches"][$key]["conductor"]=null;
                        $dia["vuelta"]["horas"][$hora]["coches"][$key]["pasajeros"]=[];
                    }
                }
            }
        }
        if(isset($this->condiciones["conductores"][$conductor]))$this->condiciones["conductores"][$conductor]--;
    }
    /**
     * Asigna los pasajeros para un día completo
     * @param Object $dia Lista de viajes de un día
     */
    public function asignarPasajeros(&$dia){
        foreach ($dia["ida"]["horas"] as $hora => $viaje) {
            if(!$this->asignarPasajerosEnCoches($dia["ida"]["horas"][$hora])){
                return false;
            }
        }
        foreach ($dia["vuelta"]["horas"] as $hora => $viaje) {
            if(!$this->asignarPasajerosEnCoches($dia["vuelta"]["horas"][$hora],$dia["ida"]["horas"])){
                return false;
            }
        }
        return true;
    }
    /**
     * Reparte los viajeros de una hora en coches
     * @param Object $viaje Contiene los viajeros, conductores y coches de una hora
     */
    public function asignarPasajerosEnCoches(&$viaje,$ida=null) {
        $i=0;
        foreach ($viaje["viajeros"] as $idViajero=>$viajero) {
            // Si el viajero no es conducto
            if(!in_array($idViajero,$viaje["conductores"])){
                $probados = 0;
                $colocado =false;
                $dondeVuelve = false;
                if($ida!=null){//Si es viaje de vuelta busca cual ha sido su salida
                    $dondeVuelve = $this->buscarSalida($ida,$idViajero);
                }
                // Reparte a los viajeros uno en cada coche
                while ($probados<count($viaje["coches"]) && !$colocado) {
                    // Lo pone en un coche que tenga plazas
                    if(count($viaje["coches"][$i]["pasajeros"])<$viaje["coches"][$i]["plazas"]) {
                        // Comprueba si el coche es de vuelta y vuelve al mismo sitio que salio el viajero
                        if($dondeVuelve!==null && $dondeVuelve ===$viaje["coches"][$i]["salida"] ){
                            $viaje["coches"][$i]["pasajeros"][]=$idViajero;
                            $colocado = true;
                        } else {
                            $viaje["coches"][$i]["pasajeros"][]=$idViajero;
                            $colocado = true;
                        }
                    }
                    // Si no vale prueba otro coche
                    $i++;
                    if($i>=count($viaje["coches"]))$i=0;
                }
                if(!$colocado){
                    return false;
                }
            }
        }
        return true;
    }
    /**
     *  Busca  de donde salia el coche de un viajero
     */
    public function buscarSalida($viajes,$idViajero) {
        foreach ($viajes as $hora => $viaje) {
            foreach ($viaje["coches"] as $key => $coche) {
                if(!in_array($idViajero,$coche["pasajeros"])){
                    return $coche["salida"];
                }
            }
        }
        return false;
    }
    /**
     * Comrpueba si queda algún viajero por comprobar segun la última posición comprobada
     * @param Integer $posicion Última posición comprobada
     * @param Object $viaje Contiene los viajeros, conductores y coches de una hora
     */
    public function quedanViajeros($posicion,$viaje){
        return $posicion+1<count($viaje["viajeros"]);
    }


    /**
     * Almacena la
     * @param int $idRueda
     * @param array $rueda Datos a almacenar
     */
    public function guardarRuedaGenerada($idRueda=0,$rueda=[]){

        function guardarViaje($idRueda,$dia,$tipo,$viajes){
            foreach ($viajes["horas"] as $hora=>$viaje) {
                RuedaGenerada::create([
                    "id_rueda"=>$idRueda,
                    "dia"=>$dia,
                    "hora"=>$hora,
                    "tipo"=>$tipo,
                    "coches"=>json_encode($viaje["coches"])
                ]);
            }
        }

        // Funcion para notificar a los usuarios
        function notificarUsuarios($id){
            $users = User::where("rueda",$id)->get();
            foreach ($users as $user) {
                if(filter_var($user->email, FILTER_VALIDATE_EMAIL)){
                    $url = env('APP_ROUTE');
                Mail::to($user->email)->send(new RuedaRehecha($user->name, $user->surname, $url));
                }
            }
        }
        // Elimina los posibles datos existentes de la rueda
        RuedaGenerada::where("id_rueda",$idRueda)->delete();
        foreach ($rueda as $dia => $viajes) {
            guardarViaje($idRueda,$dia,1,$viajes["ida"]);
            guardarViaje($idRueda,$dia,2,$viajes["vuelta"]);
        }

        notificarUsuarios($idRueda);
    }

    /**
     * @param $semana
     */
    public function pintarTabla($semana)
    {
        echo "<table style='border: 1px solid;width: 100%;border-collapse: collapse;'>
                <tbody style='display: flex;'>";
        foreach ($semana as $dia => $viajes) {
            echo "<tr style='display: flex; flex-direction: column; flex: 1;'>";
            echo "<th style='border: 1px solid;'>$dia</th>";
            foreach ($viajes['ida']["horas"] as $viaje) {
                $this->pintarCeldas($viaje);
            }
            echo "<td style='border-width: 3px 0px; border-style: solid;'></td>";
            foreach ($viajes['vuelta']["horas"] as $viaje) {
                $this->pintarCeldas($viaje);
            }
            echo "</tr>";
        }
        echo "</tbody></table>";

    }

    public function pintarCeldas($viaje)
    {
        echo "<td style='border: 1px solid; height: 100px;overflow: auto;display: flex;'>";
        // foreach ($viaje["coches"] as $i => $coche) {
        //     echo "<div style='flex: 1;padding: 1px;'>";
        //     if (isset($viaje['viajeros'][$coche['conductor']])) {
        //         echo "<b>" . $viaje['viajeros'][$coche['conductor']]->name . " " . $viaje['viajeros'][$coche['conductor']]->surname . "</b>";
        //     }
        //     echo "<ul>";
        //     foreach ($coche['pasajeros'] as $id => $viajero) {
        //         echo "<li>".$viaje['viajeros'][$viajero]->name . " " . $viaje['viajeros'][$viajero]->surname ."</li>";
        //     }
        //     echo "</ul>";
        //     echo "</div>";
        // }
        echo "<ul>";
        foreach ($viaje['viajeros'] as $id => $viajero) {
            echo "<li>".$viajero->name . " " . $viajero->surname . " " . $viajero->id ."</li>";
        }
        echo "</ul>";
        echo "</td>";
    }

    /**
     * Agrega una rueda al servidor
     */
    public function addRueda(Request $params){
        $params->validate([
            'nombre'     => 'required|string',
            'origen'    => 'string',
        ]);
        $rueda = Rueda::create([
            "nombre"=>$params->nombre,
            "descripcion"=>$params->descripcion,
            "origen"=>$params->origen,
            "destino"=>"IFP Virgen de Gracia"
        ]);
        $this->updateRuedaSalidas($rueda->id,$params->salidas);
        $this->addRuedaViajes($rueda->id);

        return response()->json($rueda, 201);
    }
    public function addRuedaViajes($idRueda){
        // Crea los viajes de la rueda
        $horas = ["08:30","09:25","10:20","12:40","13:35","14:30"];
        for ($i=0; $i < 5; $i++) {
            foreach($horas AS $key=>$hora){
                \App\Models\Rueda_viaje::create([
                    "id_rueda"=>$idRueda,
                    "dia"=>$i,
                    "hora"=>$hora,
                    "tipo"=>$key<3?1:2
                ]);
            }
        }
    }
    public function updateRueda(Request $params){
        $params->validate([
            'id' => 'required|integer',
            'nombre' => 'required|string',
            'descripcion' => 'string',
            'origen' => 'string',
        ]);
        $rueda = Rueda::where("id",$params->id)->first();
        $rueda["nombre"] = $params->nombre;
        $rueda["descripcion"] = $params->descripcion;
        $rueda["origen"] = $params->origen;
        $rueda->save();
        $this->updateRuedaSalidas($rueda->id,$params->salidas);
        $rueda = $this->getRuedaDB($params->id);
        return response()->json($rueda, 200);
    }
    /**
     * Borra la rueda y todos los datos asociados a ella
     * Se debe notificar a los usuarios
     */
    public function deleteRueda(Request $params,$id){
        if(!isset($id)) return abort(400);
        Rueda::where("id",$params->id)->delete();
        Rueda_salidas::where("id_rueda",$params->id)->delete();
        \App\Models\Rueda_viaje::where("id_rueda",$params->id)->delete();
        RuedaGenerada::where("id_rueda",$params->id)->delete();
        User::where("rueda",$params->id)->update(["rueda"=>null]);
        return response()->noContent();
    }
    public function updateRuedaSalidas($idRueda=0,$salidas=[]){
        $ids=[];
        foreach($salidas as $salida){
            $data = Rueda_salidas::where('id',$salida['id'])->first();
            if($data){
                $data->nombre=$salida["nombre"];
            } else {
                $data = Rueda_salidas::create([
                    "id_rueda"=>$idRueda,
                    "nombre"=>$salida["nombre"]
                ]);
            }
            $ids[]=$data["id"];
        }
        Rueda_salidas::where("id_rueda",$idRueda)->whereNotIn('id',$ids)->delete();
    }
}
