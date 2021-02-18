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

    public function roles(){
        return $this->hasOne('App\Models\Rol','id','rol');
    }

    public function users(){
        return $this->hasOne('App\Models\User', 'id','idUsuario');
    }

}
