<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Propuestas extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $fillable = [
        'nombre',
        'descripcion',
        'tipo',
        'tiempo_actual_realizacion',
        'tiempo_esperado_realizacion',
        'cantidad_actual_requisitos',
        'cantidad_esperada_requisitos',
        'fecha_cumplimiento',
    ];
    
    public function documentos ()
    {
        return $this->hasMany(PropuestasDocumentos::class, 'propuesta_id');
    }
}
