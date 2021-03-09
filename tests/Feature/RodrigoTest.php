<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;


class RodrigoTest extends TestCase
{
    use WithoutMiddleware;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    // public function test_example()
    // {
    //     $response = $this->get('/');

    //     $response->assertStatus(200);
    // }
    public function test_Crear_una_rueda_nueva(){
        DB::beginTransaction();
        $response = $this->post('api/rueda',[
            "nombre"=>"Prueba",
            "descripcion"=>"Esta rueda está creada en un test",
            "origen"=>"Murcia",
            "destino"=>"IFP Virgen de Gracia",
            "salidas"=>[
                [
                    "id"=>null,
                    "nombre"=>"Salida 1"
                ]
            ]
        ]);
        $response->assertStatus(201);
        DB::rollback();
    }
    public function test_Modificar_una_rueda(){
        DB::beginTransaction();
        $response = $this->put('api/rueda',[
            "id"=>1,
            "nombre"=>"Prueba",
            "descripcion"=>"Esta rueda está creada en un test",
            "origen"=>"Murcia",
            "destino"=>"IFP Virgen de Gracia",
            "salidas"=>[
                [
                    "id"=>null,
                    "nombre"=>"Salida 1"
                ]
            ]
        ]);
        $response->assertStatus(200);
        DB::rollback();
    }
    public function test_Eliminar_una_rueda(){
        DB::beginTransaction();
        $response = $this->delete('api/rueda/1');
        $response->assertStatus(204);
        DB::rollback();
    }
}
