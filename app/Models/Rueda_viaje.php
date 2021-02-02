<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Rueda_viaje extends Model
{
    use HasFactory, Notifiable;
    protected $table = 'ruedas_viajes';
    protected $fillable = [
        'idRueda',
        'dia',
        'hora',
        'tipo',
    ];
    protected $hidden = [
        'created_at',
        'updated_at'
    ];
    public function rueda(){
//        return $this->hasMany('App\Propiedad','DNI','DNI');
        return $this->hasOne('App\Models\Rueda','idRueda','id');
    }
    public function viajeros(){
        return $this->hasMany('App\Models\Rueda_viajes_usuario','id_rueda_viaje','id');
    }
}
