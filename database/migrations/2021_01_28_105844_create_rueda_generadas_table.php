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
            $table->timestamps();
            $table->integer("id_rueda");
            $table->integer("dia");
            $table->string("hora");
            $table->integer("tipo");
            $table->string("coches");
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
