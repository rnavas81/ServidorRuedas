<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rueda_viajes_usuario extends Model
{
    use HasFactory;
    protected $table = 'ruedas_viajes_users';
    protected $fillable = [
        'id_rueda_viaje',
        'id_usuario',
        'reglas',
    ];
    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}
