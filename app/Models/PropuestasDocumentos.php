<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropuestasDocumentos extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $fillable = [
        'propuesta_id',
        'archivo',
    ];
    
    public function propuesta ()
    {
        return $this->belongsTo(Propuestas::class, 'propuesta_id', 'id');
    }
}
