<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Rueda extends Model
{
    /*            $table->id();
            $table->timestamps();
            $table->string("");
            $table->string("");
            $table->string("");
            $table->string("");
    */
    use HasFactory, Notifiable;
    protected $fillable = [
        'nombre',
        'descripcion',
        'origen',
        'destino',
    ];
    protected $hidden = [
        'created_at',
        'updated_at'
    ];
    public function viajes(){
        return $this->hasMany('App\Models\Rueda_viaje','idRueda','id');
    }
}
