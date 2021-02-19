<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Crea los usuarios
        \App\Models\User::create([
            'name'     => 'admin',
            'surname'  => '',
            'email'    => 'carshare.ifpvdg@gmail.com',
            'password' => bcrypt("Chubaca2020"),

        ]);
        for ($i=0; $i < 10; $i++) {
            \App\Models\User::create([
                'name'     => "user$i",
                'surname'  => '',
                'email'    => "mail$i@nomail.com",
                'password' => bcrypt("123"),

            ]);
        }

        \App\Models\Rol::create([
            "rol"=>"Adminsitrador"
        ]);
        \App\Models\Rol::create([
            "rol"=>"Usuario"
        ]);
        \App\Models\AsignacionRol::create([
            'idUsuario'=>1,
            'rol'=>1,
        ]);
        for ($i=2; $i <= 10; $i++) {
            \App\Models\AsignacionRol::create([
                'idUsuario'=>$i,
                'rol'=>$i<5?1:2,
            ]);
        }

        // Crea una rueda de prueba
        \App\Models\Rueda::create([
            "nombre"=>"IFP Virgen de Gracia",
            "descripcion"=>"Las viajes de ida salen 30 minutos antes",
            "origen"=>"Ciudad Real",
            "destino"=>"IFP Virgen de Gracia"
        ]);
        // Crea los viajes de la rueda
        $horas = ["08:30","09:25","10:20","12:40","13:35","14:30"];
        for ($i=0; $i < 5; $i++) {
            foreach($horas AS $key=>$hora){
                \App\Models\Rueda_viaje::create([
                    "id_rueda"=>1,
                    "dia"=>$i,
                    "hora"=>$hora,
                    "tipo"=>$key<3?1:2
                ]);
            }
        }
        // Asigna aleatoriamente los usuarios a los viajes
        $usuarios = \App\Models\User::get();
        $viajes = \App\Models\Rueda_viaje::get();
        foreach($usuarios AS $usuario){
            for ($i=1; $i <= 10; $i++) {
                $min = $i*3-2;
                $max = $i*3;
                $idViaje = rand($min,$max);
                \App\Models\Rueda_viajes_usuario::create([
                    "id_rueda_viaje"=>$idViaje,
                    "id_usuario"=>$usuario->id,
                    "reglas"=>""
                ]);
            }
        }
        // Genera la rueda
        app("\App\Http\Controllers\Api\Ruedas")->generateRueda(1);

    }
}
