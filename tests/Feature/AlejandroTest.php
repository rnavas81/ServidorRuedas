<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\User;
use App\Http\Controllers\api\Usuarios;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class AlejandroTest extends TestCase
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

    public function test_createUser()
    {
        DB::beginTransaction();
        $response = $this->post('api/administrador/createUser',[
            "email"=>"probando@gmail.com",
            "name"=>"Prueba",
            "password"=>bcrypt('1234'),
            "rol"=>2,
            "surname" => "Probando Apellidos"
        ]);
        $response->assertStatus(201);
        DB::rollback();
    }

    public function test_modifyUser()
    {
        DB::beginTransaction();
        $response = $this->post('api/administrador/editUser',[
            "editEmail"=>"probando@gmail.com",
            "editName"=>"Prueba",
            "editPassword1"=>"",
            "editPassword2"=>null,
            "editRol"=>2,
            "editSurname" => "Apellidos",
            "idUsuario" => "23"
        ]);
        $response->assertStatus(200);
        DB::rollback();
    }
}
