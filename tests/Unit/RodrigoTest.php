<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Http\Controllers\Api\Ruedas;

class RodrigoTest extends TestCase
{
    private $horas = ['8:30','9:25','10:20'];
    /**
     * Comprueba si un día está cubierto
     */
    public function test_Si_a_una_lista_de_tres_horas_le_paso_2_el_dia_esta_cubierto() {
        $ruedas = new Ruedas();
        $this->assertTrue($ruedas->estaDiaCubierto($this->horas,2));
    }
    public function test_Si_a_una_lista_de_tres_horas_le_paso_0_el_dia_no_esta_cubierto() {
        $ruedas = new Ruedas();
        $this->assertFalse($ruedas->estaDiaCubierto($this->horas,0));
    }
}
