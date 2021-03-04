<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rueda;
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
        $ruedas = Rueda::get();
        return response()->json($ruedas,200);
    }

    /**
     * Recupera los datos básicos y los viajes de una rueda
     * @param Integer $id Identificador de la rueda
     */
    public function getRueda($id = null)
    {
        if($id==null){
            $rueda = Rueda::with("viajes")->where("id",1)->first();
        } else {
            $rueda = Rueda::with("viajes")->where("id",$id)->first();
        }

        return response()->json($rueda, 200);
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
            $tmp = json_decode($rueda->generada[$i]->coches);
            $rueda->generada[$i]->coches = $tmp;
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
        // $this->pintarTabla($semana);exit;
        // if(count($this->condiciones['viajeros'])<count($dias)){
        //     dd($this->condiciones);
        //     $this->condiciones['max']=
        // }
        do{
            $exito = $this->generarCuadrante($semana,$dias,$horas,0,0,$p);
            if(!$exito){
                //Prueba el siguiente viajero de la primera hora
                if($this->condiciones["max"]<count($dias)) {
                    $this->output("Nuevo intento:$p ".$this->condiciones["max"],true);
                    $p=0;
                    $this->condiciones["max"]++;
                //Agrega un nuevo coche
                } else {
                    $coches++;
                    $this->output("Agrega coche:$p ".$this->condiciones["max"]." $coches",true);
                    $this->agregarCocheSemana($semana);
                    $this->condiciones["max"]=1;
                    $p=0;
                }
                // $p++;
            }
        }while(!$exito);
        // Para hacer pruebas
        // $this->pintarTabla($semana);
        // Almacena la tabla generada
        if($exito)$this->guardarRuedaGenerada($id,$semana);
        return $exito;
    }

    private function agregarCocheSemana(&$semana){
        $diaCambiar=null;
        $horaCambiar=null;
        $max=0;
        foreach($semana AS $diaKey=>$dia){
            foreach ($dia["ida"]["horas"] as $hora => $viaje) {
                if(count($viaje['viajeros']) ==  count($viaje['coches'])) continue;
                try {
                    $avg = count($viaje['viajeros']) / count($viaje['coches']);
                } catch (\Throwable $th) {
                    $avg = 0;
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
        $this->agregarCoche($semana[$diaCambiar]["ida"],$horaCambiar);
        $horaCambiar=null;
        $max=0;
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
        $this->agregarCoche($semana[$diaCambiar]["vuelta"],$horaCambiar);
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
                if(!in_array($viajero->usuario->id,$this->condiciones["viajeros"]))$this->condiciones["viajeros"][]=$viajero->usuario->id;
                $semana[$viaje->dia][$viaje->tipo == 1 ? "ida" : "vuelta"]["horas"][$viaje->hora]["viajeros"][$viajero->usuario->id] = $viajero->usuario;
            }
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
    private function ajustarCoches(&$semana,&$horas){
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
                        if (count($viaje['viajeros']) > count($viajes[$tipo]["horas"][$repartir?:$hora])) {
                            $max = $avg;
                            $repartir = $hora;
                        }
                    }
                }
                $this->agregarCoche($viajes[$tipo],$repartir);
                // $viajes[$tipo]["horas"][$repartir]["coches"][] = [
                //     "conductor" => null,
                //     "pasajeros" => [],
                // ];
                // $viajes[$tipo]["totalcoches"]++;
            }
            $semana[$diaN] = $viajes;
        }
    }

    private function agregarCoche(&$dia,$hora) {
        $dia["horas"][$hora]["coches"][] = [
            "conductor" => null,
            "pasajeros" => [],
        ];
        $dia["totalcoches"]++;

    }
    /**
     * Función para sacar texto por pantall durante la ejecución del código
     */
    private function output($texto="",$empiezaLinea=false){
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
    private function generarCuadrante(&$cuadrante,$dias,$horas,$dia=0,$hora=0,$pos=0){
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
                        $this->output("NO HAY VIAJEROS");
                        // if($this->estaDiaCubierto($cuadrante[$dias[$dia]])){
                        if($this->estaDiaCubierto($horas,$hora)){
                            $exito = $this->generarCuadrante($cuadrante,$dias,$horas,$dia+1,0,0);
                            if($exito){
                                $this->asignarPasajeros($cuadrante[$dias[$dia]]);
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
                    $this->output("[$dia $horas[$hora]] POSICION:$posicion ID:$conductor->id ==> ".implode(",",array_keys($cuadrante[$dias[$dia]]["ida"]["horas"][$horas[$hora]]["viajeros"])),true);
                // Comprueba si puede ser conductor
                if ($conductor && $this->puedeSerConductor($cuadrante, $horas[$hora], $dias[$dia], $conductor)) {
                    $this->asignarConductor($cuadrante, $horas[$hora], $dias[$dia], $conductor->id);
                    // Comprueba los coche cubiertos
                    if ($this->estaHoraCubierta($cuadrante[$dias[$dia]]["ida"]["horas"][$horas[$hora]])) {
                        //Si en ese día ya tenemos todos los conductores... pasamos al siguiente día.
                        // if($this->estaDiaCubierto($cuadrante[$dias[$dia]])){
                        if($this->estaDiaCubierto($horas,$hora)){
                            $this->output("DIA CUBIERTO");
                            $exito = $this->generarCuadrante($cuadrante,$dias,$horas,$dia+1,0,0);
                            if($exito){
                                $this->asignarPasajeros($cuadrante[$dias[$dia]]);
                            } else {
                                $this->cancelarConductor($cuadrante[$dias[$dia]],$conductor->id);
                            }
                        // Si todos los coches están cubiertos pasa a la siguiente hora
                        } else {
                            $this->output("COCHES CUBIERTOS");
                            $exito = $this->generarCuadrante($cuadrante,$dias,$horas,$dia,$hora+1,0);
                            if(!$exito){
                                $this->cancelarConductor($cuadrante[$dias[$dia]],$conductor->id);
                                $esImposible=true;
                            }
                        }

                    } else {
                        $this->output("QUEDAN COCHES");
                        // Si quedan viajeros para asignar se intenta
                        if($this->quedanViajeros($posicion,$cuadrante[$dias[$dia]]["ida"]["horas"][$horas[$hora]])) {
                            //Pasamos al siguiente conductor de los disponibles en esa franja.
                            $exito = $this->generarCuadrante($cuadrante,$dias,$horas,$dia,$hora,$posicion+1);
                            if(!$exito){
                                $this->cancelarConductor($cuadrante[$dias[$dia]],$conductor->id);
                            }
                        } else {
                            $this->output(" PERO NO CONDUCTORES");
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
        global $condiciones;
        $id = $conductor->id;
        $viajes = $cuadrante[$dia]["vuelta"]["horas"];
        if(in_array($id,$cuadrante[$dia]["ida"]["horas"][$hora]["conductores"])){
            return false;
        }
        $haConducido = $this->cuantasVecesConduce($id);
        $this->output("Conducciones: $haConducido |");
        if($haConducido>=$this->condiciones["max"]) return false;
        $response = $this->puedeSerConductorVuelta($viajes, $conductor->id,count($cuadrante[$dia]["ida"]["horas"][$hora]["viajeros"])==1);
        $this->output(" ".($response?"SI":"NO")." puede ser conductor");
        return $response;
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
     * @arg $conductores array lista de conductores
     *
     */
    private function cuantasVecesConduce($id){
        if(array_key_exists($id,$this->condiciones["conductores"]))return $this->condiciones["conductores"][$id];
        else return 0;
    }
    /**
     * Comprueba si todos los coches de una hora están cubiertos
     */
    private function estaHoraCubierta($hora){
        try {
            count($hora["coches"]) == count($hora["conductores"]);
            //code...
        } catch (\Throwable $th) {
            //throw $th;
        }
        return count($hora["coches"]) == count($hora["conductores"]);
    }
    /**
     * Comprueba que todos los coches de las horas de un día están cubiertos
     */
    private function estaDiaCubierto($horas,$hora){
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
    private function asignarConductor(&$cuadrante, $hora, $dia, $id){
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
        $this->output(" ||| ASIGNADO ||| ");
        return $correcto1 && $correcto2;
    }
    /**
     * Asigna un conductor a un coche de un dia
     * @param Object $viaje Contiene los conductores, viajeros y coches de una hora
     * @param Integer $id Identificador de viajer que se asigna como conductor
     */
    private function asignarConductorViaje(&$viaje,$id){
        $correcto = false;
        for ($key=0; !$correcto && $key < count($viaje["coches"]); $key++) {
            $coche=$viaje["coches"][$key];
            if($coche["conductor"]==null || $coche["conductor"] === $id){
                $viaje["coches"][$key]["conductor"]=$id;
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
    private function cancelarConductor(&$dia,$conductor){
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
        $this->output(" CANCELADO $conductor||");
    }
    /**
     * Asigna los pasajeros para un día completo
     * @param Object $dia Lista de viajes de un día
     */
    private function asignarPasajeros(&$dia){
        foreach ($dia["ida"]["horas"] as $hora => $viaje) {
            $this->asignarPasajerosEnCoches($dia["ida"]["horas"][$hora]);
        }
        foreach ($dia["vuelta"]["horas"] as $hora => $viaje) {
            $this->asignarPasajerosEnCoches($dia["vuelta"]["horas"][$hora]);
        }
    }
    /**
     * Reparte los viajeros de una hora en coches
     * @param Object $viaje Contiene los viajeros, conductores y coches de una hora
     */
    private function asignarPasajerosEnCoches(&$viaje) {
        $i=0;
        foreach ($viaje["viajeros"] as $idViajero=>$viajero) {
            if(!in_array($idViajero,$viaje["conductores"])){
                $viaje["coches"][$i]["pasajeros"][]=$idViajero;
                $i++;
                if($i>=count($viaje["coches"]))$i=0;
            }
        }

    }
    /**
     * Comrpueba si queda algún viajero por comprobar segun la última posición comprobada
     * @param Integer $posicion Última posición comprobada
     * @param Object $viaje Contiene los viajeros, conductores y coches de una hora
     */
    private function quedanViajeros($posicion,$viaje){
        return $posicion+1<count($viaje["viajeros"]);
    }


    /**
     * Almacena la
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

        // Funcion para notificar a los usuarios
        function notificarUsuarios($id){
            $users = User::where("rueda",$id)->get();
            foreach ($users as $user) {
                $url = env('APP_ROUTE');
                Mail::to($user->email)->send(new RuedaRehecha($user->name, $user->surname, $url));
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
    private function pintarTabla($semana)
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

    private function pintarCeldas($viaje)
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
            echo "<li>".$viajero->name . " " . $viajero->surname ."</li>";
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
        $rueda = Rueda::where("id",$params->id)->update([
            'nombre'=>$params->nombre,
            'descripcion'=>$params->descripcion,
            'origen'=>$params->origen,
        ]);
        return response()->json($rueda, 200);
    }
    public function deleteRueda(Request $params,$id){
        if(!isset($id)) return abort(400);
        Rueda::where("id",$params->id)->delete();
        return response()->noContent();
    }
}
