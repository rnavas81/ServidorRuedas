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
    public function test_example()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
    
    public function test_crearUsuario(){
        $user = new User;
        $user->name = 'User';
        $user->surname = 'surname';
        $user->email = 'mail@nomail.com';
        $user->password = bcrypt('123456');
        $user->save();
        
        $this->assertEquals('User', $user->name);
        $this->assertEquals('surname', $user->surname);
        $this->assertEquals('mail@nomail.com', $user->email);
        $this->assertEquals(0, $user->status);
    }
    
    public function test_login() {
        $response = $this->post('api/login', ['email' => 'carshare.ifpvdg@gmail.com', 'password' => 'Chubaca2020']);
        
        
        $response->assertStatus(200);
    }
    
    public function test_recuperar() {
        $response = $this->post('api/forget', ['email' => 'mail1@nomail.com']);
        
        $response->assertStatus(200);
    }
    
    public function test_alfanumerico(){
        $string = app(AuthController2::class)->generarAlfanumerico(0,15);
       
        $this->assertEquals('15', strlen($string));
    }
    
    public function test_token() {
        $response = $this->post('api/login', ['email' => 'carshare.ifpvdg@gmail.com', 'password' => 'Chubaca2020']);
        
        $token = $response->json();
        $token = $token['access_token'];
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '. $token,
        ])->post('api/usuario/test');
        
        $response->assertDontSeeText(0);
    }
}
