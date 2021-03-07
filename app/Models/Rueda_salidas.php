<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rueda_salidas extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'ruedas_salidas';
    protected $fillable = [
        'id_rueda',
        'nombre'
    ];
    protected $hidden = [
    ];
    public function rueda(){
        return $this->hasOne('App\Models\Rueda','id','id_rueda');
    }
}
