<?php

namespace Tests\Traits;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

trait TestsWithRoles
{
    /**
     * Configura roles y permisos para las pruebas
     */
    protected function setUpRolesAndPermissions(): void
    {
        // Limpiar cache de permisos y roles
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear roles solo si no existen
        $adminSistemaRole = Role::firstOrCreate(['name' => 'admin-sistema']);
        $adminGeneralRole = Role::firstOrCreate(['name' => 'admin-general']);
        $usuarioSeiRole = Role::firstOrCreate(['name' => 'usuario-sei']);
        $solicitanteRole = Role::firstOrCreate(['name' => 'solicitante']);

        // Crear permisos solo si no existen
        $permissions = [
            'usuarios.ver.all',
            'usuarios.crear.any',
            'usuarios.editar.any',
            'usuarios.eliminar.any',
            'usuarios.password.cambiar',
            'catalogos.crud',
            'solicitudes.ver.all',
            'solicitudes.clasificar',
            'solicitudes.en.proceso.ver',
            'solicitudes.asignadas.ver',
            'solicitudes.asignar.usuarios',
            'acciones.crud',
            'comentarios.crud',
            'evidencias.descargar',
            'evidencias.ver',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Asignar permisos a roles
        $adminSistemaRole->givePermissionTo($permissions);
        $adminGeneralRole->givePermissionTo([
            'usuarios.ver.all',
            'usuarios.editar.any',
            'catalogos.crud',
            'solicitudes.asignar.usuarios',
            'acciones.crud',
            'comentarios.crud',
            'evidencias.descargar',
            'evidencias.ver',
        ]);
        $usuarioSeiRole->givePermissionTo([
            'solicitudes.asignadas.ver',
            'solicitudes.clasificar',
            'solicitudes.en.proceso.ver',
            'acciones.crud',
            'comentarios.crud',
            'evidencias.descargar',
            'evidencias.ver',
        ]);
        $solicitanteRole->givePermissionTo([
            'comentarios.crud',
            'evidencias.descargar',
            'evidencias.ver',
        ]);
    }

    /**
     * Crea un usuario con rol específico
     */
    protected function createUserWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole($role);
        return $user;
    }

    /**
     * Crea un usuario admin-sistema
     */
    protected function createAdminSistemaUser(array $attributes = []): User
    {
        return $this->createUserWithRole('admin-sistema', $attributes);
    }

    /**
     * Crea un usuario admin-general
     */
    protected function createAdminGeneralUser(array $attributes = []): User
    {
        return $this->createUserWithRole('admin-general', $attributes);
    }

    /**
     * Crea un usuario SEI
     */
    protected function createSeiUser(array $attributes = []): User
    {
        return $this->createUserWithRole('usuario-sei', $attributes);
    }

    /**
     * Crea un usuario solicitante
     */
    protected function createSolicitanteUser(array $attributes = []): User
    {
        return $this->createUserWithRole('solicitante', $attributes);
    }
}