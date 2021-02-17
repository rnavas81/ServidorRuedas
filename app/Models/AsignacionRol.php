<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsignacionRol extends Model
{
    use HasFactory;
    protected $fillable = [
        'idUsuario',
        'rol',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
