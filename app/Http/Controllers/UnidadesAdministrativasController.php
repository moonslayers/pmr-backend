<?php

namespace App\Http\Controllers;

use App\Models\UnidadesAdministrativas;
use Illuminate\Http\Request;

class UnidadesAdministrativasController extends SuperController
{
    public function __construct () {
        parent::__construct (UnidadesAdministrativas::class);
        
    }
    
    
}
