<?php

namespace App\Http\Controllers;

use App\Models\Propuestas;
use Illuminate\Http\Request;

class PropuestasController extends SuperController
{
    public function __construct ()
    {
        parent::__construct(Propuestas::class);
        
        $this->mainRelations = [
            'documentos',
        ];
    }
}
