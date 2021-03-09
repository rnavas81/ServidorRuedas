<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Rueda extends Model
{
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
    public function salidas(){
        return $this->hasMany('App\Models\Rueda_salidas','id_rueda','id');
    }
    public function viajes(){
        return $this->hasMany('App\Models\Rueda_viaje','id_rueda','id');
    }

    public function generada() {
        return $this->hasMany('App\Models\RuedaGenerada','id_rueda','id');
    }
}
