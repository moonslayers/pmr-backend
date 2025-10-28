<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\TestsWithRoles;

class UsuarioControllerTest extends TestCase
{
    use RefreshDatabase, TestsWithRoles;

    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Configurar roles y permisos
        $this->setUpRolesAndPermissions();

        // Crear usuarios de prueba sin conflicto con el SuperAdminSeeder
        $this->adminUser = $this->createAdminSistemaUser([
            'email' => 'test_admin@sagem.com',
            'password' => Hash::make('admin123456'),
            'rfc' => 'ADMN800101HAA',
        ]);

        $this->regularUser = $this->createSolicitanteUser([
            'email' => 'test_user@sagem.com',
            'password' => Hash::make('password123'),
            'rfc' => 'USER800101HAA',
        ]);
    }

    /**
     * Test obtener lista de usuarios (index).
     */
    public function test_get_users_index(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Crear usuarios adicionales para pruebas
        User::factory()->count(5)->create();

        $response = $this->getJson('/api/usuarios');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data',
                'page',
                'per_page',
                'total_pages',
                'total_items'
            ])
            ->assertJson([
                'status' => true,
                'message' => 'Consulta exitosa'
            ]);

        // Verificar que hay usuarios en la respuesta
        $this->assertGreaterThan(0, count($response->json('data')));
    }

    /**
     * Test obtener lista de usuarios sin autenticación.
     */
    public function test_get_users_index_without_authentication(): void
    {
        $response = $this->getJson('/api/usuarios');

        $response->assertStatus(401);
    }

    /**
     * Test crear un nuevo usuario (store).
     */
    public function test_create_user(): void
    {
        Sanctum::actingAs($this->adminUser);

        $userData = [
            'data' => [
                'name' => 'Nuevo Usuario',
                'email' => 'nuevo@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'rfc' => 'PERJ010101HA1',
                'user_type' => 'EXTERNO',
                'role' => 'solicitante'
            ]
        ];

        $response = $this->postJson('/api/usuarios', $userData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Usuario creado y rol asignado correctamente'
            ]);

        // Verificar que el usuario fue creado en la base de datos
        $this->assertDatabaseHas('users', [
            'name' => 'Nuevo Usuario',
            'email' => 'nuevo@example.com'
        ]);

        // Verificar que el password fue hasheado
        $createdUser = User::where('email', 'nuevo@example.com')->first();
        $this->assertTrue(Hash::check('password123', $createdUser->password));
    }

    /**
     * Test crear usuario sin autenticación.
     */
    public function test_create_user_without_authentication(): void
    {
        $userData = [
            'data' => [
                'name' => 'Nuevo Usuario',
                'email' => 'nuevo@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'rfc' => 'PERJ010101HA1',
                'user_type' => 'EXTERNO',
                            ]
        ];

        $response = $this->postJson('/api/usuarios', $userData);

        $response->assertStatus(401);
    }

    /**
     * Test validación al crear usuario con datos inválidos.
     */
    public function test_create_user_validation_errors(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Test sin nombre
        $response = $this->postJson('/api/usuarios', [
            'data' => [
                'email' => 'nuevo@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'rfc' => 'TEST800101123',
                'user_type' => 'EXTERNO',
                                'role' => 'solicitante'
            ]
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false
            ]);

        // Test email inválido
        $response = $this->postJson('/api/usuarios', [
            'data' => [
                'name' => 'Nuevo Usuario',
                'email' => 'email_invalido',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'rfc' => 'TEST800101123',
                'user_type' => 'EXTERNO',
                                'role' => 'solicitante'
            ]
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false
            ]);

        // Test password demasiado corto
        $response = $this->postJson('/api/usuarios', [
            'data' => [
                'name' => 'Nuevo Usuario',
                'email' => 'nuevo@example.com',
                'password' => '123',
                'password_confirmation' => '123',
                'rfc' => 'TEST800101123',
                'user_type' => 'EXTERNO',
                                'role' => 'solicitante'
            ]
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false
            ]);

        // Test passwords no coinciden
        $response = $this->postJson('/api/usuarios', [
            'data' => [
                'name' => 'Nuevo Usuario',
                'email' => 'nuevo@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password456',
            ]
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false
            ]);
    }

    /**
     * Test crear usuario con email duplicado.
     */
    public function test_create_user_duplicate_email(): void
    {
        Sanctum::actingAs($this->adminUser);

        $userData = [
            'data' => [
                'name' => 'Nuevo Usuario',
                'email' => $this->regularUser->email, // Email existente
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'rfc' => 'TEST800101123',
                'user_type' => 'EXTERNO',
                                'role' => 'solicitante'
            ]
        ];

        $response = $this->postJson('/api/usuarios', $userData);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false
            ]);
    }

    /**
     * Test obtener un usuario específico (show).
     */
    public function test_get_user_by_id(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson("/api/usuarios/{$this->regularUser->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJson([
                'status' => true,
                'message' => 'Solicitud Correcta',
                'data' => [
                    'id' => $this->regularUser->id,
                    'name' => $this->regularUser->name,
                    'email' => $this->regularUser->email
                ]
            ]);
    }

    /**
     * Test obtener usuario que no existe.
     */
    public function test_get_nonexistent_user(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->getJson('/api/usuarios/9999');

        $response->assertStatus(404)
            ->assertJson([
                'status' => false,
                'message' => 'El registro con id 9999 no existe'
            ]);
    }

    /**
     * Test actualizar un usuario existente (update).
     */
    public function test_update_user(): void
    {
        Sanctum::actingAs($this->adminUser);

        $updateData = [
            'data' => [
                'name' => 'Usuario Actualizado',
                'email' => 'actualizado@example.com',
            ]
        ];

        $response = $this->putJson("/api/usuarios/{$this->regularUser->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true
            ]);

        // Verificar que los datos fueron actualizados
        $this->assertDatabaseHas('users', [
            'id' => $this->regularUser->id,
            'name' => 'Usuario Actualizado',
            'email' => 'actualizado@example.com'
        ]);
    }

    /**
     * Test actualizar usuario con password.
     */
    public function test_update_user_with_password(): void
    {
        Sanctum::actingAs($this->regularUser);

        $updateData = [
            'data' => [
                'name' => 'Usuario con Nuevo Password',
                'password' => 'nuevoPassword123',
                'password_confirmation' => 'nuevoPassword123',
            ]
        ];

        $response = $this->putJson("/api/usuarios/{$this->regularUser->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true
            ]);

        // Verificar que el password fue actualizado y hasheado
        $updatedUser = User::find($this->regularUser->id);
        $this->assertTrue(Hash::check('nuevoPassword123', $updatedUser->password));
    }

    /**
     * Test actualizar usuario con email duplicado.
     */
    public function test_update_user_duplicate_email(): void
    {
        Sanctum::actingAs($this->adminUser);

        $updateData = [
            'data' => [
                'email' => $this->adminUser->email, // Email de otro usuario
            ]
        ];

        $response = $this->putJson("/api/usuarios/{$this->regularUser->id}", $updateData);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false
            ]);
    }

    /**
     * Test eliminar usuario (soft delete).
     */
    public function test_delete_user(): void
    {
        Sanctum::actingAs($this->adminUser);

        $response = $this->deleteJson("/api/usuarios/{$this->regularUser->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => "El registro con id {$this->regularUser->id} ha sido eliminado."
            ]);

        // Verificar que el usuario fue soft deleted
        $this->assertSoftDeleted('users', [
            'id' => $this->regularUser->id
        ]);
    }

    /**
     * Test restaurar usuario eliminado.
     */
    public function test_restore_deleted_user(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Primero eliminar el usuario
        $this->regularUser->delete();
        $this->assertSoftDeleted('users', ['id' => $this->regularUser->id]);

        // Restaurar el usuario
        $response = $this->deleteJson("/api/usuarios/{$this->regularUser->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => "El registro con id {$this->regularUser->id} ha sido restaurado."
            ]);

        // Verificar que el usuario fue restaurado
        $this->assertNotSoftDeleted('users', ['id' => $this->regularUser->id]);
    }

    /**
     * Test búsqueda de usuarios por texto.
     */
    public function test_search_users(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Crear usuarios con nombres específicos para búsqueda
        User::factory()->create(['name' => 'Juan Pérez']);
        User::factory()->create(['name' => 'María García']);
        User::factory()->create(['email' => 'juan.perez@example.com']);

        $response = $this->getJson('/api/usuarios?search=Juan');

        $response->assertStatus(200);

        // Verificar que se encuentran usuarios con "Juan"
        $users = $response->json('data');
        $juanFound = false;
        foreach ($users as $user) {
            if (str_contains($user['name'], 'Juan') || str_contains($user['email'], 'Juan')) {
                $juanFound = true;
                break;
            }
        }
        $this->assertTrue($juanFound);
    }

    /**
     * Test paginación de usuarios.
     */
    public function test_users_pagination(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Crear usuarios adicionales
        User::factory()->count(25)->create();

        // Solicitar página 2 con 10 elementos por página
        $response = $this->getJson('/api/usuarios?page=2&per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data',
                'page',
                'per_page',
                'total_pages',
                'total_items'
            ])
            ->assertJson([
                'page' => 2,
                'per_page' => 10,
                'status' => true
            ]);
    }

    /**
     * test creación masiva de usuarios.
     */
    public function test_create_multiple_users(): void
    {
        Sanctum::actingAs($this->adminUser);

        $usersData = [
            'data' => [
                [
                    'name' => 'Usuario 1',
                    'email' => 'usuario1@example.com',
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                    'rfc' => 'USER180101123',
                    'user_type' => 'EXTERNO',
                                        'role' => 'solicitante'
                ],
                [
                    'name' => 'Usuario 2',
                    'email' => 'usuario2@example.com',
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                    'rfc' => 'USER280101456',
                    'user_type' => 'EXTERNO',
                                        'role' => 'solicitante'
                ],
                [
                    'name' => 'Usuario 3',
                    'email' => 'usuario3@example.com',
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                    'rfc' => 'USER380101789',
                    'user_type' => 'EXTERNO',
                                        'role' => 'solicitante'
                ],
            ]
        ];

        $response = $this->postJson('/api/usuarios', $usersData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true
            ]);

        // Verificar que los usuarios fueron creados
        $this->assertDatabaseHas('users', ['email' => 'usuario1@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'usuario2@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'usuario3@example.com']);
    }

    /**
     * Test actualización masiva de usuarios.
     */
    public function test_update_multiple_users(): void
    {
        Sanctum::actingAs($this->adminUser);

        // Crear usuarios adicionales
        $user1 = User::factory()->create(['name' => 'Usuario Original 1']);
        $user2 = User::factory()->create(['name' => 'Usuario Original 2']);

        $updateData = [
            'data' => [
                [
                    'id' => $user1->id,
                    'name' => 'Usuario Actualizado 1',
                ],
                [
                    'id' => $user2->id,
                    'name' => 'Usuario Actualizado 2',
                ],
            ]
        ];

        $response = $this->putJson('/api/usuarios/multiple', $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => '2 registros fueron actualizados.'
            ]);

        // Verificar que los usuarios fueron actualizados
        $this->assertDatabaseHas('users', [
            'id' => $user1->id,
            'name' => 'Usuario Actualizado 1'
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $user2->id,
            'name' => 'Usuario Actualizado 2'
        ]);
    }

    /**
     * Test obtener lista de usuarios con columnas específicas.
     */
    public function test_get_users_with_specific_columns(): void
    {
        Sanctum::actingAs($this->adminUser);

        $columns = json_encode(['id', 'name', 'email']);
        $response = $this->getJson("/api/usuarios?columns={$columns}");

        $response->assertStatus(200);

        $users = $response->json('data');
        if (!empty($users)) {
            $this->assertArrayHasKey('id', $users[0]);
            $this->assertArrayHasKey('name', $users[0]);
            $this->assertArrayHasKey('email', $users[0]);
            $this->assertArrayNotHasKey('password', $users[0]);
        }
    }
}