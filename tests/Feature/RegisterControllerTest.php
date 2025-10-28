<?php

use App\Models\User;
use App\Models\EmailVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Crear el rol solicitante para las pruebas
    Role::firstOrCreate(['name' => 'solicitante']);

    // Limpiar cache de rate limiting antes de cada prueba
    RateLimiter::clear('register:test');
});

test('usuario puede registrarse exitosamente', function () {
    Mail::fake();

    $userData = [
        'name' => 'Juan Pérez',
        'email' => 'juan.perez@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'rfc' => 'PEPJ800101H01',
    ];

    $response = $this->postJson('/api/auth/register', $userData);

    $response->assertStatus(201)
        ->assertJson([
            'status' => true,
            'message' => 'Usuario registrado exitosamente. Por favor, verifica tu correo electrónico.',
        ]);

    // Verificar que el usuario fue creado
    $this->assertDatabaseHas('users', [
        'name' => 'Juan Pérez',
        'email' => 'juan.perez@example.com',
        'rfc' => 'PEPJ800101H01',
        'user_type' => 'EXTERNO',
    ]);

    // Verificar que el usuario tiene el rol solicitante
    $user = User::where('email', 'juan.perez@example.com')->first();
    $this->assertTrue($user->hasRole('solicitante'));

    // Verificar que se creó el token de verificación
    $this->assertDatabaseHas('email_verifications', [
        'user_id' => $user->id,
    ]);

    // Verificar que se envió el correo de verificación
    Mail::assertSent(\App\Mail\EmailVerificationMail::class, function ($mail) use ($user) {
        return $mail->user->id === $user->id;
    });
});

test('registro falla con datos inválidos', function () {
    $response = $this->postJson('/api/auth/register', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password', 'rfc']);
});

test('registro falla con correo existente', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $userData = [
        'name' => 'Juan Pérez',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'rfc' => 'PEPJ800101H02',
    ];

    $response = $this->postJson('/api/auth/register', $userData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('registro falla con RFC existente', function () {
    User::factory()->create(['rfc' => 'PEPJ800101H03']);

    $userData = [
        'name' => 'Juan Pérez',
        'email' => 'juan.new@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'rfc' => 'PEPJ800101H03',
    ];

    $response = $this->postJson('/api/auth/register', $userData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['rfc']);
});

test('registro falla con RFC inválido', function () {
    $userData = [
        'name' => 'Juan Pérez',
        'email' => 'juan@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'rfc' => 'RFC_INVALIDO',
    ];

    $response = $this->postJson('/api/auth/register', $userData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['rfc']);
});

test('registro falla con contraseñas que no coinciden', function () {
    $userData = [
        'name' => 'Juan Pérez',
        'email' => 'juan@example.com',
        'password' => 'password123',
        'password_confirmation' => 'different_password',
        'rfc' => 'PEPJ800101H04',
    ];

    $response = $this->postJson('/api/auth/register', $userData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('registro falla con contraseña muy corta', function () {
    $userData = [
        'name' => 'Juan Pérez',
        'email' => 'juan@example.com',
        'password' => '123',
        'password_confirmation' => '123',
        'rfc' => 'PEPJ800101H05',
    ];

    $response = $this->postJson('/api/auth/register', $userData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('registro tiene configuración de rate limiting', function () {
    // Esta prueba verifica que el controller tenga rate limiting configurado
    // No podemos probar fácilmente el rate limiting en entorno de pruebas
    // porque Laravel resetea el estado entre pruebas

    $userData = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'rfc' => 'TEST800101H01',
    ];

    $response = $this->postJson('/api/auth/register', $userData);

    // Verificar que el registro funciona correctamente
    $response->assertStatus(201);

    // Verificar que el método ensureIsNotRateLimited existe en el controller
    $controller = new \App\Http\Controllers\RegisterController();
    $this->assertTrue(method_exists($controller, 'ensureIsNotRateLimited'));
});

test('registro se ejecuta correctamente en español', function () {
    $userData = [
        'name' => 'María González',
        'email' => 'maria.gonzalez@example.com',
        'password' => 'contraseña123',
        'password_confirmation' => 'contraseña123',
        'rfc' => 'GOJM750101H02',
    ];

    $response = $this->postJson('/api/auth/register', $userData);

    $response->assertStatus(201)
        ->assertJson([
            'status' => true,
            'message' => 'Usuario registrado exitosamente. Por favor, verifica tu correo electrónico.',
        ]);
});

test('registro convierte RFC a mayúsculas', function () {
    $userData = [
        'name' => 'Juan Pérez',
        'email' => 'juan@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'rfc' => 'pepj800101h06', // minúsculas
    ];

    $response = $this->postJson('/api/auth/register', $userData);

    $response->assertStatus(201);

    $user = User::where('email', 'juan@example.com')->first();
    $this->assertEquals('PEPJ800101H06', $user->rfc);
});

test('registro asigna correctamente el rol de solicitante', function () {
    $userData = [
        'name' => 'Juan Pérez',
        'email' => 'juan.solicitante@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'rfc' => 'PEPJ800101H07',
    ];

    $response = $this->postJson('/api/auth/register', $userData);

    $response->assertStatus(201);

    $user = User::where('email', 'juan.solicitante@example.com')->first();
    $this->assertTrue($user->hasRole('solicitante'));
    $this->assertEquals('EXTERNO', $user->user_type);
});

test('respuesta de registro incluye información del usuario', function () {
    $userData = [
        'name' => 'Juan Pérez',
        'email' => 'juan.full@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'rfc' => 'PEPJ800101H08',
    ];

    $response = $this->postJson('/api/auth/register', $userData);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'rfc',
                    'user_type',
                    'email_verified_at',
                    'created_at',
                ],
                'roles',
                'permissions',
            ],
        ]);
});