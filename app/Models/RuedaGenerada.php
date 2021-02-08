<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RuedaGenerada extends Model
{
    use HasFactory;
    protected $table = 'rueda_generadas';
    public $timestamps = false;

    protected $fillable = [
        'id_rueda',
        'dia',
        'hora',
        'tipo',
        'coches',
    ];
    protected $hidden = [
    ];
}
