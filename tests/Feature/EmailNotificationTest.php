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

test('verifica_envio_email_cuando_accion_resuelve_solicitud', function () {
    Mail::fake();

    // Crear usuario que recibirá el email
    $usuarioExterno = createSolicitanteUser([
        'email' => 'test@example.com',
        'name' => 'Juan Pérez'
    ]);

    // Crear usuario interno que ejecuta la acción
    $usuarioInterno = createSeiUser(['user_type' => 'INTERNO']);

    // Crear solicitud del usuario externo
    $solicitud = Solicitud::factory()->completa()->create([
        'estatus' => 'EN PROCESO',
        'created_by' => $usuarioExterno->id,
        'descripcion_problema' => 'Problema de prueba para email'
    ]);

    // Crear acción que resuelve la solicitud
    $accionData = [
        'data' => [
            'solicitud_id' => $solicitud->id,
            'entidad_gestionada' => 'Secretaría de Economía',
            'descripcion' => 'Acción de prueba que resuelve',
            'fecha_inicio' => '2025-10-15T10:00:00',
            'resuelve_solicitud' => true
        ]
    ];

    // Ejecutar la acción
    $this->actingAs($usuarioInterno)
        ->postJson('/api/solicitud-acciones', $accionData)
        ->assertStatus(200);

    // Verificar que el email fue enviado
    Mail::assertSent(AccionRegistradaMail::class, function ($mail) use ($usuarioExterno) {
        return $mail->hasTo($usuarioExterno->email);
    });

    // Verificar contenido del email
    Mail::assertSent(AccionRegistradaMail::class, function ($mail) use ($solicitud) {
        $data = $mail->data;
        return $data['solicitud_id'] === $solicitud->id &&
            $data['resuelve_solicitud'] === true &&
            $data['ue_nombre'] === 'Juan Pérez';
    });
});

test('verifica_envio_email_cuando_accion_no_resuelve', function () {
    Mail::fake();

    $usuarioExterno = createSolicitanteUser([
        'name' => 'Carlos López',
        'email' => 'carlos@test.com'
    ]);
    $usuarioInterno = createSeiUser(['user_type' => 'INTERNO']);

    $solicitud = Solicitud::factory()->completa()->create([
        'estatus' => 'EN PROCESO',
        'created_by' => $usuarioExterno->id,
        'descripcion_problema' => 'Problema para email de acción no resolutiva'
    ]);

    // Crear acción que NO resuelve la solicitud
    $accionData = [
        'data' => [
            'solicitud_id' => $solicitud->id,
            'entidad_gestionada' => 'Secretaría de Hacienda',
            'descripcion' => 'Acción que no resuelve la solicitud',
            'fecha_inicio' => '2025-10-15T10:00:00',
            'resuelve_solicitud' => false
        ]
    ];

    $this->actingAs($usuarioInterno)
        ->postJson('/api/solicitud-acciones', $accionData)
        ->assertStatus(200);

    // Verificar que SÍ se envió email (ahora se envía para cualquier acción)
    Mail::assertSent(AccionRegistradaMail::class, function ($mail) use ($usuarioExterno) {
        return $mail->hasTo($usuarioExterno->email);
    });

    // Verificar contenido del email con resuelve_solicitud = false
    Mail::assertSent(AccionRegistradaMail::class, function ($mail) use ($solicitud, $usuarioExterno) {
        $data = $mail->data;
        return $data['solicitud_id'] === $solicitud->id &&
            $data['resuelve_solicitud'] === false &&
            $data['ue_nombre'] === 'Carlos López' &&
            $data['descripcion_problema'] === 'Problema para email de acción no resolutiva' &&
            $data['entidad_gestionada'] === 'Secretaría de Hacienda' &&
            $data['descripcion_accion'] === 'Acción que no resuelve la solicitud';
    });
});

test('verifica_datos_correctos_en_email_enviado', function () {
    Mail::fake();

    $creador = createSolicitanteUser([
        'name' => 'María González',
        'email' => 'maria@test.com'
    ]);

    $ejecutor = createSeiUser(['user_type' => 'INTERNO']);

    $solicitud = Solicitud::factory()->completa()->create([
        'estatus' => 'EN PROCESO',
        'created_by' => $creador->id,
        'descripcion_problema' => 'Necesito ayuda con trámite de crédito'
    ]);

    $accionData = [
        'data' => [
            'solicitud_id' => $solicitud->id,
            'entidad_gestionada' => 'Banco Nacional',
            'descripcion' => 'Gestión aprobada para línea de crédito',
            'fecha_inicio' => '2025-10-20T14:30:00',
            'resuelve_solicitud' => true
        ]
    ];

    $this->actingAs($ejecutor)
        ->postJson('/api/solicitud-acciones', $accionData)
        ->assertStatus(200);

    // Verificar detalles específicos del email
    Mail::assertSent(AccionRegistradaMail::class, function ($mail) use ($creador, $solicitud) {
        $data = $mail->data;

        return $mail->hasTo($creador->email) &&
            $data['solicitud_id'] === $solicitud->id &&
            $data['ue_nombre'] === 'María González' &&
            $data['descripcion_problema'] === 'Necesito ayuda con trámite de crédito' &&
            $data['entidad_gestionada'] === 'Banco Nacional' &&
            $data['descripcion_accion'] === 'Gestión aprobada para línea de crédito' &&
            $data['resuelve_solicitud'] === true;
    });
});