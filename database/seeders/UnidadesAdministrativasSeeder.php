<?php

namespace Database\Seeders;

use App\Models\UnidadesAdministrativas;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnidadesAdministrativasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info("ℹ️ Creando unidades administrativas");
        
        $data = [
            'Subsecretaría de Planeación Económica',
            'Subsecretaría de Fomento Económico',
            'Subsecretaría de Gestión de Inversión',
            'Subsecretaría de Industrias Creativas',
        ];
        
        foreach ($data as $d) {
            UnidadesAdministrativas::create([
                'nombre' => $d,
            ]);
        }
        
        $this->command->info('✅ Catálogo de unidades administrativas creado');
    }
}
