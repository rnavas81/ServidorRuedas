<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RuedaGenerada extends Model
{
    use HasFactory;
    protected $table = 'ruedas_generadas';

    protected $fillable = [
        'id_rueda',
        'dia',
        'hora',
        'tipo',
        'coches',
    ];
    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}
