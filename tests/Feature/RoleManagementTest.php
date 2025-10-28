<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\TestSQLiteRoles;
use Tests\Traits\TestUsers;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase, TestUsers;

    protected function setUp(): void
    {
        parent::setUp();

        // Configurar roles y permisos una sola vez
        $this->setUpRolesAndPermissions();

        // Crear usuarios de prueba con diferentes roles (reutilizados en todas las pruebas)
        $this->adminSistema = $this->createAdminSistemaUser(['email' => 'admin@test.com']);
        $this->adminGeneral = $this->createAdminGeneralUser(['email' => 'admin-general@test.com']);
        $this->usuarioSei = $this->createSeiUser(['email' => 'sei@test.com']);
        $this->solicitante = $this->createSolicitanteUser(['email' => 'solicitante@test.com']);
        $this->testUser = $this->createSolicitanteUser(['email' => 'testuser@test.com']);
    }

    public function test_puede_listar_todos_los_roles_disponibles()
    {
        $response = $this->actingAs($this->adminSistema)
            ->getJson('/api/roles');

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Roles obtenidos exitosamente'
            ]);

        $data = $response->json('data');
        $this->assertCount(4, $data); // admin-sistema, admin-general, usuario-sei, solicitante

        // Verificar estructura de datos
        $roleNames = collect($data)->pluck('name')->toArray();
        $this->assertContains('admin-sistema', $roleNames);
        $this->assertContains('admin-general', $roleNames);
        $this->assertContains('usuario-sei', $roleNames);
        $this->assertContains('solicitante', $roleNames);
    }

    public function test_puede_obtener_roles_disponibles_para_asignar_formato_ligero()
    {
        $response = $this->actingAs($this->adminSistema)
            ->getJson('/api/roles/available');

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Roles disponibles obtenidos exitosamente'
            ]);

        $data = $response->json('data');
        $this->assertCount(4, $data);

        // Verificar estructura simplificada
        $this->assertArrayHasKey('value', $data[0]);
        $this->assertArrayHasKey('label', $data[0]);
        $this->assertArrayHasKey('description', $data[0]);
    }

    public function test_puede_obtener_permisos_de_un_rol_especifico()
    {
        $response = $this->actingAs($this->adminSistema)
            ->getJson('/api/roles/usuario-sei/permissions');

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Permisos del rol obtenidos exitosamente'
            ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('role', $data);
        $this->assertArrayHasKey('permissions', $data);
        $this->assertEquals('usuario-sei', $data['role']['name']);
        $this->assertNotEmpty($data['permissions']);
    }

    public function test_puede_obtener_permisos_de_rol_admin_sistema()
    {
        $response = $this->actingAs($this->adminSistema)
            ->getJson('/api/roles/admin-sistema/permissions');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals('admin-sistema', $data['role']['name']);
        $this->assertCount(14, $data['permissions']); // Todos los permisos disponibles
    }

    public function test_retorna_error_404_al_obtener_permisos_de_rol_inexistente()
    {
        $response = $this->actingAs($this->adminSistema)
            ->getJson('/api/roles/rol-inexistente/permissions');

        $response->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => 'Rol no encontrado'
            ]);
    }

    public function test_puede_asignar_rol_a_un_usuario_existente()
    {
        $response = $this->actingAs($this->adminSistema)
            ->postJson("/api/usuarios/{$this->testUser->id}/assign-role", [
                'role' => 'usuario-sei'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Rol asignado exitosamente'
            ]);

        $data = $response->json('data');
        $this->assertEquals($this->testUser->id, $data['user_id']);
        $this->assertEquals('usuario-sei', $data['assigned_role']);
        $this->assertContains('solicitante', $data['previous_roles']);

        // Verificar en base de datos
        $this->testUser->refresh();
        $this->assertTrue($this->testUser->hasRole('usuario-sei'));
        $this->assertFalse($this->testUser->hasRole('solicitante'));
    }

    public function test_puede_ver_roles_actuales_de_un_usuario()
    {
        $response = $this->actingAs($this->adminSistema)
            ->getJson("/api/usuarios/{$this->testUser->id}/roles");

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Roles del usuario obtenidos exitosamente'
            ]);

        $data = $response->json('data');
        $this->assertEquals($this->testUser->id, $data['user']['id']);
        $this->assertCount(1, $data['roles']);
        $this->assertEquals('solicitante', $data['roles'][0]['name']);
    }

    public function test_usuario_no_admin_no_puede_asignar_roles()
    {
        $response = $this->actingAs($this->usuarioSei)
            ->postJson("/api/usuarios/{$this->testUser->id}/assign-role", [
                'role' => 'admin-general'
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => false,
                'message' => 'Acceso denegado. No tienes los permisos necesarios para realizar esta acción.'
            ]);
    }

    public function test_usuario_no_autenticado_no_puede_acceder_a_rutas_de_gestion_de_roles()
    {
        $response = $this->getJson('/api/roles');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.'
            ]);
    }

    public function test_admin_sistema_no_puede_modificar_su_propio_rol()
    {
        $response = $this->actingAs($this->adminSistema)
            ->postJson("/api/usuarios/{$this->adminSistema->id}/assign-role", [
                'role' => 'usuario-sei'
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => false,
                'message' => 'Operación no permitida'
            ]);
    }

    public function test_admin_sistema_puede_mantener_su_propio_rol_admin_sistema()
    {
        $response = $this->actingAs($this->adminSistema)
            ->postJson("/api/usuarios/{$this->adminSistema->id}/assign-role", [
                'role' => 'admin-sistema'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Rol asignado exitosamente'
            ]);
    }

    public function test_valida_que_el_rol_exista_al_asignar()
    {
        $response = $this->actingAs($this->adminSistema)
            ->postJson("/api/usuarios/{$this->testUser->id}/assign-role", [
                'role' => 'rol-inexistente'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
    }

    public function test_retorna_error_404_al_asignar_rol_a_usuario_inexistente()
    {
        $nonExistentUserId = 9999;
        $response = $this->actingAs($this->adminSistema)
            ->postJson("/api/usuarios/{$nonExistentUserId}/assign-role", [
                'role' => 'usuario-sei'
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => 'Usuario no encontrado'
            ]);
    }

    public function test_admin_general_puede_listar_roles_pero_no_asignar()
    {
        // admin-general tiene permiso usuarios.editar.any, debería poder listar
        $listResponse = $this->actingAs($this->adminGeneral)
            ->getJson('/api/roles');

        $listResponse->assertStatus(200);

        // Pero el controlador valida específicamente que sea admin-sistema para asignar
        $assignResponse = $this->actingAs($this->adminGeneral)
            ->postJson("/api/usuarios/{$this->testUser->id}/assign-role", [
                'role' => 'usuario-sei'
            ]);

        $assignResponse->assertStatus(403)
            ->assertJson([
                'status' => false,
                'message' => 'Acceso denegado'
            ]);
    }

    public function test_verificar_estructura_completa_de_respuesta_de_roles()
    {
        $response = $this->actingAs($this->adminSistema)
            ->getJson('/api/roles');

        $response->assertStatus(200);

        $data = $response->json('data');
        $adminRole = collect($data)->firstWhere('name', 'admin-sistema');

        $this->assertArrayHasKey('name', $adminRole);
        $this->assertArrayHasKey('display_name', $adminRole);
        $this->assertArrayHasKey('description', $adminRole);
        $this->assertArrayHasKey('permissions_count', $adminRole);
        $this->assertArrayHasKey('created_at', $adminRole);
        $this->assertEquals('Administrador del Sistema', $adminRole['display_name']);
        $this->assertStringContainsString('Acceso completo', $adminRole['description']);
        $this->assertEquals(14, $adminRole['permissions_count']);
    }

    public function test_verificar_estructura_completa_de_respuesta_de_permisos()
    {
        $response = $this->actingAs($this->adminSistema)
            ->getJson('/api/roles/solicitante/permissions');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertArrayHasKey('name', $data['permissions'][0]);
        $this->assertArrayHasKey('display_name', $data['permissions'][0]);
        $this->assertArrayHasKey('description', $data['permissions'][0]);
        $this->assertArrayHasKey('module', $data['permissions'][0]);

        // Verificar que solicitante tenga los permisos correctos
        $permissionNames = collect($data['permissions'])->pluck('name')->toArray();
        $this->assertContains('comentarios.crud', $permissionNames);
        $this->assertContains('evidencias.descargar', $permissionNames);
        $this->assertContains('evidencias.ver', $permissionNames);
    }

    public function test_asignacion_multiple_de_roles_funciona_correctamente()
    {
        // Primera asignación
        $response1 = $this->actingAs($this->adminSistema)
            ->postJson("/api/usuarios/{$this->testUser->id}/assign-role", [
                'role' => 'usuario-sei'
            ]);

        $response1->assertStatus(200);

        // Segunda asignación (debería reemplazar el rol anterior)
        $response2 = $this->actingAs($this->adminSistema)
            ->postJson("/api/usuarios/{$this->testUser->id}/assign-role", [
                'role' => 'admin-general'
            ]);

        $response2->assertStatus(200);

        $data = $response2->json('data');
        $this->assertEquals('admin-general', $data['assigned_role']);
        $this->assertContains('usuario-sei', $data['previous_roles']);

        // Verificar estado final
        $this->testUser->refresh();
        $this->assertTrue($this->testUser->hasRole('admin-general'));
        $this->assertFalse($this->testUser->hasRole('usuario-sei'));
        $this->assertFalse($this->testUser->hasRole('solicitante'));
    }
}