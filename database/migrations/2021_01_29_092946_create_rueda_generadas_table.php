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
        Schema::create('ruedas_generadas', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer("id_rueda");
            $table->tinyInteger("dia");
            $table->string("hora");
            $table->tinyInteger("tipo");
            $table->text('coches');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ruedas_generadas');
    }
}
