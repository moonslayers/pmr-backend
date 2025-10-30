<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Iniciando seeders de PMR...');

        // Crear el super admin principal
        $this->call(SuperAdminSeeder::class);

        // Crear los roles y permisos del sistema
        $this->call(RolesAndPermissionsSeeder::class);
        
        // Crear los catálogos Iniciales de los usuarios externos
        $this->call(UnidadesAdministrativasSeeder::class);

        // Opcional: Crear usuarios de prueba para desarrollo
        // Descomenta las siguientes líneas si necesitas usuarios de prueba

        // Crear usuarios de prueba predecibles
        // User::factory()->testUser()->create();

        // Crear 5 usuarios de prueba secuenciales
        // User::factory()->testUsers(5)->create();

        $this->command->info('✅ Seeders de PMR completados exitosamente.');
        $this->command->info('');
        $this->command->info('👤 Usuario creado:');
        $this->command->info('   Super Admin: admin@pmr.com / admin123456');
        $this->command->info('');
        $this->command->info('🔐 Roles del sistema:');
        $this->command->info('   Admin-sistema, Admin-general, Usuario-interno, Usuario-externo');
        $this->command->info('');
        $this->command->info('💡 Para crear usuarios de prueba adicionales,');
        $this->command->info('   descomenta las líneas en DatabaseSeeder.php');
    }
}
