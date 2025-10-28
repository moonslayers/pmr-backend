<?php

namespace Tests\Traits;

use DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

trait TestUsers
{
    public $adminSistema;
    public $adminGeneral;
    public $testUser;
    public $usuarioSei;
    /**
     * Configura roles y permisos para las pruebas
     */
    public function setUpRolesAndPermissions(): void
    {
        // Limpiar cache de permisos y roles
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear roles
        $adminSistemaRole = Role::firstOrCreate(['name' => 'admin-sistema']);
        $adminGeneralRole = Role::firstOrCreate(['name' => 'admin-general']);
        $usuarioSeiRole = Role::firstOrCreate(['name' => 'usuario-sei']);
        $solicitanteRole = Role::firstOrCreate(['name' => 'solicitante']);

        // Crear permisos
        $permissions = [
            'usuarios.ver.all',
            'usuarios.crear.any',
            'usuarios.editar.any',
            'usuarios.eliminar.any',
            'usuarios.password.cambiar',
            'catalogos.crud',
            'solicitudes.ver.all',
            'solicitudes.clasificar',
            'solicitudes.asignar',
            'solicitudes.en.proceso.ver',
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
            'acciones.crud',
            'comentarios.crud',
            'evidencias.descargar',
            'evidencias.ver',
        ]);
        $usuarioSeiRole->givePermissionTo([
            'solicitudes.ver.all',
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
    public function createUserWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole($role);
        return $user;
    }

    /**
     * Crea un usuario admin-sistema
     */
    public function createAdminSistemaUser(array $attributes = []): User
    {
        if (!$this->adminSistema) {
            $this->adminSistema = $this->createUserWithRole('admin-sistema', $attributes);
        }
        return $this->adminSistema;
    }

    /**
     * Crea un usuario admin-general
     */
    public function createAdminGeneralUser(array $attributes = []): User
    {
        if (!$this->adminGeneral) {
            $this->adminGeneral = $this->createUserWithRole('admin-general', $attributes);
        }
        return $this->adminGeneral;
    }

    /**
     * Crea un usuario SEI
     */
    public function createSeiUser(array $attributes = []): User
    {
        if (!$this->usuarioSei) {
            $this->usuarioSei = $this->createUserWithRole('usuario-sei', $attributes);
        }
        return $this->usuarioSei;
    }

    /**
     * Crea un usuario solicitante
     */
    public function createSolicitanteUser(array $attributes = []): User
    {
        if (!$this->testUser) {
            $this->testUser = $this->createUserWithRole('solicitante', $attributes);
        }
        return $this->testUser;
    }
}