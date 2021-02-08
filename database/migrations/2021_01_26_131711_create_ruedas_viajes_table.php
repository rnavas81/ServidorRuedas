<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRuedasViajesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ruedas_viajes', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer("id_rueda");
            $table->tinyInteger("dia");
            $table->string("hora");
            $table->tinyInteger("tipo");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ruedas_viajes');
    }
}
