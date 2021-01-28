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
        'dia',
        'hora',
        'tipo',
    ];
    protected $hidden = [
        'idRueda',
        'created_at',
        'updated_at'
    ];
    public function rueda(){
//        return $this->hasMany('App\Propiedad','DNI','DNI');
        return $this->hasOne('App\Models\Rueda','idRueda','id');
    }
}
