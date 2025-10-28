<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Schema;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar caché de permisos y roles
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear permisos
        $permissions = [
            // Permisos de usuarios
            'usuarios.ver.all',
            'usuarios.crear.any',
            'usuarios.editar.own',
            'usuarios.editar.any',
            'usuarios.eliminar.any',
            'usuarios.password.cambiar',

            // Permisos de solicitudes
            'solicitudes.ver.all',
            'solicitudes.clasificar',
            'solicitudes.asignar',
            'solicitudes.en.proceso.ver',
            'solicitudes.asignadas.ver',     // Para ver solicitudes asignadas a uno mismo
            'solicitudes.asignar.usuarios',   // Para asignar solicitudes a usuarios SEI

            // Permisos de catálogos
            'catalogos.crud',

            // Permisos de acciones de solicitudes
            'acciones.crud',

            // Permisos de comentarios
            'comentarios.crud',

            // Permisos de evidencias
            'evidencias.descargar',
            'evidencias.ver',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Crear roles y asignar permisos

        // 1. Rol: admin-sistema (acceso total)
        $adminSistema = Role::firstOrCreate(['name' => 'admin-sistema']);
        $adminSistema->givePermissionTo($permissions);

        // 2. Rol: admin-general (casi todo el acceso, menos configuración crítica)
        $adminGeneral = Role::firstOrCreate(['name' => 'admin-general']);
        $adminGeneral->givePermissionTo([
            'usuarios.ver.all',
            'usuarios.crear.any',
            'usuarios.editar.any', // Pero no eliminar ni cambiar passwords de otros admins
            'usuarios.eliminar.any',

            'solicitudes.ver.all',
            'solicitudes.clasificar',
            'solicitudes.asignar',
            'solicitudes.en.proceso.ver',
            'solicitudes.asignar.usuarios',  // Nuevo permiso para asignar solicitudes a usuarios SEI
            
            'catalogos.crud',
            'acciones.crud',
            'comentarios.crud',
            'evidencias.descargar',
            'evidencias.ver',
        ]);

        // 3. Rol: usuario-sei (operaciones del día a día)
        $usuarioSei = Role::firstOrCreate(['name' => 'usuario-sei']);
        $usuarioSei->givePermissionTo([
            'solicitudes.asignadas.ver',     // Cambiado: solo ver solicitudes asignadas a él mismo
            'solicitudes.clasificar',
            'solicitudes.en.proceso.ver',
            'acciones.crud',
            'comentarios.crud',
            'evidencias.descargar',
            'evidencias.ver',
        ]);

        // 4. Rol: solicitante (solo operaciones propias)
        $solicitante = Role::firstOrCreate(['name' => 'solicitante']);
        $solicitante->givePermissionTo([
            'comentarios.crud', // Puede comentar en sus propias solicitudes
            'evidencias.descargar', // Puede descargar sus propias evidencias
            'evidencias.ver', // Puede ver sus propias evidencias
        ]);

        // Asignar rol admin-sistema al usuario super admin si existe
        $admin = User::whereEmail(env('SUPER_ADMIN_EMAIL', 'admin@pmr.com'))->first();

        if ($admin && count($admin->getRoleNames()) == 0) {
            $admin->assignRole('admin-sistema');
        }

        $this->command->info('✅ Roles y permisos creados exitosamente:');
        $this->command->info('📋 admin-sistema: Acceso completo al sistema');
        $this->command->info('👔 admin-general: Acceso administrativo (sin configuración crítica)');
        $this->command->info('🏢 usuario-sei: Operaciones de SEI del día a día');
        $this->command->info('📝 solicitante: Operaciones propias de solicitantes externos');
    }
}
