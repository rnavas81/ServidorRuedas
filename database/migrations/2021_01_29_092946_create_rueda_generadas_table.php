<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRuedaGeneradasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rueda_generadas', function (Blueprint $table) {
            $table->id();
            $table->integer("idRueda");
            $table->tinyInteger("dia");
            $table->string("hora");
            $table->tinyInteger("tipo");
            $table->string('coches');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rueda_generadas');
    }
}
