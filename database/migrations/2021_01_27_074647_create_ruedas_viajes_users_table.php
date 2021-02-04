<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRuedasViajesUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ruedas_viajes_users', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer("id_rueda_viaje");
            $table->integer("id_usuario");
            $table->string("reglas");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ruedas_viajes_users');
    }
}
