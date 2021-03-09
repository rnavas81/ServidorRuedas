<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Http\Controllers\Auth\AuthController2;

class JorgeTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    
    public function test_Devuelve_el_rol_correctamente(){
        $response = $this->post('api/login', ['email' => 'carshare.ifpvdg@gmail.com', 'password' => 'Chubaca2020']);
        
        $token = $response->json();
        $token = $token['access_token'];
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->post('api/usuario/testRol');
        
        $response->assertStatus(200);
    }
    
    public function test_Se_hace_login_correctamente() {
        $response = $this->post('api/login', ['email' => 'carshare.ifpvdg@gmail.com', 'password' => 'Chubaca2020']);
        
        
        $response->assertStatus(200);
    }
    
    public function test_Recuperar_contraseña_genera_nueva_pass() {
        $response = $this->post('api/forget', ['email' => 'mail1@nomail.com']);
        
        $response->assertStatus(200);
    }
    
    public function test_funcion_alfanumerico_genera_correctamente(){
        $string = app(AuthController2::class)->generarAlfanumerico(0,15);
       
        $this->assertEquals('15', strlen($string));
    }
    
    public function test_se_comprueba_que_el_token_es_correcto() {
        $response = $this->post('api/login', ['email' => 'carshare.ifpvdg@gmail.com', 'password' => 'Chubaca2020']);
        
        $token = $response->json();
        $token = $token['access_token'];
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->post('api/usuario/test');
        
        $response->assertDontSeeText(0);
    }
}
