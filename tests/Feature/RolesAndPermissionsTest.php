<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Limpiar cache de permisos y roles
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Crear roles y permisos de prueba
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

test('admin-sistema puede crear usuarios con cualquier rol', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin-sistema');

    $userData = [
        'name' => 'Usuario Test',
        'email' => 'test@example.com',
        'password' => 'password123',
        'rfc' => 'TEST800101123', // RFC válido para persona física (13 caracteres: 4 letras, 6 dígitos fecha, 3 dígitos homónima)
        'user_type' => 'INTERNO',
        'tipo_persona' => 'fisica',
        'role' => 'admin-general'
    ];

    $response = $this->actingAs($admin)
        ->postJson('/api/usuarios', ['data' => $userData]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'message' => 'Usuario creado y rol asignado correctamente'
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'rfc' => 'TEST800101123'
    ]);

    $newUser = User::where('email', 'test@example.com')->first();
    $this->assertTrue($newUser->hasRole('admin-general'));
});

test('admin-general no puede crear usuarios', function () {
    $adminGeneral = User::factory()->create();
    $adminGeneral->assignRole('admin-general');

    $userData = [
        'name' => 'Usuario Test',
        'email' => 'test2@example.com',
        'password' => 'password123',
        'rfc' => 'TEST800101456', // RFC válido para persona física (13 caracteres: 4 letras, 6 dígitos fecha, 3 dígitos homónima)
        'user_type' => 'INTERNO',
        'tipo_persona' => 'fisica',
        'role' => 'usuario-sei'
    ];

    $response = $this->actingAs($adminGeneral)
        ->postJson('/api/usuarios', ['data' => $userData]);

    $response->assertStatus(403)
        ->assertJson([
            'status' => false,
            'message' => 'Acceso denegado. No tienes los permisos necesarios para realizar esta acción.'
        ]);
});

test('usuario-sei no puede ver todos los usuarios', function () {
    $usuarioSei = User::factory()->create();
    $usuarioSei->assignRole('usuario-sei');

    $response = $this->actingAs($usuarioSei)
        ->getJson('/api/usuarios');

    $response->assertStatus(403)
        ->assertJson([
            'status' => false,
            'message' => 'Acceso denegado. No tienes los permisos necesarios para realizar esta acción.'
        ]);
});

test('admin-sistema puede ver todos los usuarios', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin-sistema');

    User::factory()->count(3)->create();

    $response = $this->actingAs($admin)
        ->getJson('/api/usuarios');

    $response->assertStatus(200);
    // Verificar que hay 4 usuarios en total
    $this->assertCount(4, $response->json('data')); // Admin + 3 usuarios creados
});

test('solicitante puede actualizar sus propios datos', function () {
    $solicitante = User::factory()->create();
    $solicitante->assignRole('solicitante');

    $updateData = [
        'name' => 'Nombre Actualizado',
        'email' => $solicitante->email,
        'role' => 'solicitante'
    ];

    $response = $this->actingAs($solicitante)
        ->putJson("/api/usuarios/{$solicitante->id}", ['data' => $updateData]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'message' => 'Usuario actualizado y rol asignado correctamente'
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $solicitante->id,
        'name' => 'Nombre Actualizado'
    ]);
});

test('admin-sistema puede cambiar contraseña de otros usuarios', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin-sistema');

    $user = User::factory()->create();
    $user->assignRole('solicitante');

    $updateData = [
        'password' => 'nuevoPassword123',
        'role' => 'solicitante'
    ];

    $response = $this->actingAs($admin)
        ->putJson("/api/usuarios/{$user->id}", ['data' => $updateData]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'message' => 'Usuario actualizado y rol asignado correctamente'
        ]);
});

test('admin-general no puede cambiar contraseña de otros usuarios', function () {
    $adminGeneral = User::factory()->create();
    $adminGeneral->assignRole('admin-general');

    $user = User::factory()->create();
    $user->assignRole('solicitante');

    $updateData = [
        'password' => 'nuevoPassword123',
        'role' => 'solicitante'
    ];

    $response = $this->actingAs($adminGeneral)
        ->putJson("/api/usuarios/{$user->id}", ['data' => $updateData]);

    $response->assertStatus(403)
        ->assertJson([
            'status' => false,
            'message' => 'No se permite modificar la contraseña de otro usuario. Permiso requerido: usuarios.password.cambiar'
        ]);
});

test('usuario puede actualizar su propia contraseña', function () {
    $user = User::factory()->create();
    $user->assignRole('solicitante');

    $updateData = [
        'password' => 'miNuevoPassword456',
        'role' => 'solicitante'
    ];

    $response = $this->actingAs($user)
        ->putJson("/api/usuarios/{$user->id}", ['data' => $updateData]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'message' => 'Usuario actualizado y rol asignado correctamente'
        ]);
});

test('admin-sistema no puede ser modificado por otros roles', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin-sistema');

    $adminGeneral = User::factory()->create();
    $adminGeneral->assignRole('admin-general');

    $updateData = [
        'name' => 'Intento modificar admin',
        'role' => 'admin-sistema'
    ];

    $response = $this->actingAs($adminGeneral)
        ->putJson("/api/usuarios/{$admin->id}", ['data' => $updateData]);

    $response->assertStatus(403)
        ->assertJson([
            'status' => false,
            'message' => 'No se permite modificar la información del usuario administrador del sistema'
        ]);
});
