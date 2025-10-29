<?php

use App\Models\User;
use App\Models\Solicitud;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Mail\AccionRegistradaMail;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Limpiar cache de permisos y roles
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Crear roles
    $adminSistemaRole = Role::create(['name' => 'admin-sistema']);
    $adminGeneralRole = Role::create(['name' => 'admin-general']);
    $usuarioSeiRole = Role::create(['name' => 'usuario-sei']);
    $solicitanteRole = Role::create(['name' => 'solicitante']);

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
        Permission::create(['name' => $permission]);
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
});

// Helper functions para crear usuarios con roles
function createSolicitanteUser($attributes = []) {
    $user = User::factory()->create($attributes);
    $user->assignRole('solicitante');
    return $user;
}

function createSeiUser($attributes = []) {
    $user = User::factory()->create($attributes);
    $user->assignRole('usuario-sei');
    return $user;
}