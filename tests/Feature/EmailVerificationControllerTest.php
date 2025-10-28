<?php

use App\Models\User;
use App\Models\EmailVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Carbon\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Limpiar cache de rate limiting antes de cada prueba
    RateLimiter::clear('email-verify:test');
});

test('puede verificar correo electrónico con token válido', function () {
    $user = User::factory()->create(['email_verified_at' => null]);
    $verification = EmailVerification::createForUser($user);

    // Verificar estado inicial
    $this->assertNull($user->email_verified_at);
    $this->assertNotNull($verification->token);

    $response = $this->postJson('/api/auth/verify-email', [
        'token' => $verification->token,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'message' => 'Correo electrónico verificado exitosamente.',
        ]);

    // Verificar que el usuario tiene el correo verificado
    $user->refresh();
    $this->assertNotNull($user->email_verified_at, 'El usuario debería tener el correo verificado');

    // Verificar que el token fue eliminado
    $this->assertDatabaseMissing('email_verifications', [
        'token' => $verification->token,
    ]);
});

test('verificación falla con token inválido', function () {
    $response = $this->postJson('/api/auth/verify-email', [
        'token' => str_repeat('a', 64), // Token de 64 caracteres inválido
    ]);

    $response->assertStatus(404)
        ->assertJson([
            'status' => false,
            'message' => 'El token de verificación es inválido o ha expirado.',
        ]);
});

test('verificación falla con token expirado', function () {
    $user = User::factory()->create(['email_verified_at' => null]);
    $verification = EmailVerification::factory()->expired()->create(['user_id' => $user->id]);

    $response = $this->postJson('/api/auth/verify-email', [
        'token' => $verification->token,
    ]);

    $response->assertStatus(410)
        ->assertJson([
            'status' => false,
            'message' => 'El token de verificación ha expirado.',
        ]);

    // Verificar que el token expirado fue eliminado
    $this->assertDatabaseMissing('email_verifications', [
        'token' => $verification->token,
    ]);
});

test('verificación falla sin proporcionar token', function () {
    $response = $this->postJson('/api/auth/verify-email', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['token']);
});

test('verificación falla con token de tamaño incorrecto', function () {
    $response = $this->postJson('/api/auth/verify-email', [
        'token' => 'token_corto',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['token']);
});

test('respuesta de verificación incluye datos del usuario', function () {
    $user = User::factory()->create([
        'name' => 'Juan Pérez',
        'email' => 'juan@example.com',
        'rfc' => 'PEPJ800101H01',
        'email_verified_at' => null,
    ]);
    $verification = EmailVerification::createForUser($user);

    $response = $this->postJson('/api/auth/verify-email', [
        'token' => $verification->token,
    ]);

    $response->assertStatus(200)
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
                'verified_at',
            ],
        ]);
});

test('puede reenviar correo de verificación', function () {
    Mail::fake();

    $user = User::factory()->create(['email_verified_at' => null]);
    $oldVerification = EmailVerification::createForUser($user);

    $response = $this->postJson('/api/auth/resend-verification', [
        'email' => $user->email,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'message' => 'Se ha enviado un nuevo correo de verificación.',
        ]);

    // Verificar que el token antiguo fue eliminado
    $this->assertDatabaseMissing('email_verifications', [
        'token' => $oldVerification->token,
    ]);

    // Verificar que se creó un nuevo token
    $this->assertDatabaseHas('email_verifications', [
        'user_id' => $user->id,
    ]);

    // Verificar que se envió el correo
    Mail::assertSent(\App\Mail\EmailVerificationMail::class, function ($mail) use ($user) {
        return $mail->user->id === $user->id;
    });
});

test('reenvío falla con correo no registrado', function () {
    $response = $this->postJson('/api/auth/resend-verification', [
        'email' => 'no_existe@example.com',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('reenvío falla con correo ya verificado', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->postJson('/api/auth/resend-verification', [
        'email' => $user->email,
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'status' => false,
            'message' => 'Este correo electrónico ya ha sido verificado previamente.',
        ]);
});

test('reenvío falla con formato de correo inválido', function () {
    $response = $this->postJson('/api/auth/resend-verification', [
        'email' => 'correo_invalido',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('reenvío falla sin proporcionar correo', function () {
    $response = $this->postJson('/api/auth/resend-verification', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('reenvío tiene configuración de rate limiting', function () {
    $user = User::factory()->create(['email_verified_at' => null]);

    $response = $this->postJson('/api/auth/resend-verification', [
        'email' => $user->email,
    ]);

    // Verificar que el reenvío funciona correctamente
    $response->assertStatus(200);

    // Verificar que el método ensureIsNotRateLimited existe en el controller
    $controller = new \App\Http\Controllers\EmailVerificationController();
    $this->assertTrue(method_exists($controller, 'ensureIsNotRateLimited'));
});

test('puede verificar estado de correo electrónico', function () {
    // Usuario con correo verificado
    $verifiedUser = User::factory()->create(['email_verified_at' => now()]);

    // Usuario con correo no verificado pero con token activo
    $unverifiedUser = User::factory()->create(['email_verified_at' => null]);
    EmailVerification::createForUser($unverifiedUser);

    // Usuario con correo no verificado sin token activo
    $noTokenUser = User::factory()->create(['email_verified_at' => null]);

    // Verificar usuario con correo confirmado
    $response = $this->postJson('/api/auth/check-verification', [
        'email' => $verifiedUser->email,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'data' => [
                'email' => $verifiedUser->email,
                'is_verified' => true,
                'has_pending_verification' => false,
            ],
        ]);

    // Verificar usuario con correo no confirmado pero con token activo
    $response = $this->postJson('/api/auth/check-verification', [
        'email' => $unverifiedUser->email,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'data' => [
                'email' => $unverifiedUser->email,
                'is_verified' => false,
                'has_pending_verification' => true,
            ],
        ]);

    // Verificar usuario sin token activo
    $response = $this->postJson('/api/auth/check-verification', [
        'email' => $noTokenUser->email,
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => true,
            'data' => [
                'email' => $noTokenUser->email,
                'is_verified' => false,
                'has_pending_verification' => false,
            ],
        ]);
});

test('verificación de estado falla con correo no registrado', function () {
    $response = $this->postJson('/api/auth/check-verification', [
        'email' => 'no_existe@example.com',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('verificación de estado falla con formato de correo inválido', function () {
    $response = $this->postJson('/api/auth/check-verification', [
        'email' => 'correo_invalido',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('verificación de estado falla sin proporcionar correo', function () {
    $response = $this->postJson('/api/auth/check-verification', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('respuesta de verificación de estado incluye timestamp de verificación', function () {
    $verifiedAt = now()->subMinutes(30);
    $user = User::factory()->create(['email_verified_at' => $verifiedAt]);

    $response = $this->postJson('/api/auth/check-verification', [
        'email' => $user->email,
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'data' => [
                'email',
                'is_verified',
                'has_pending_verification',
                'email_verified_at',
            ],
        ])
        ->assertJson([
            'status' => true,
            'data' => [
                'email' => $user->email,
                'is_verified' => true,
                'has_pending_verification' => false,
            ],
        ]);

    // Verificar que el timestamp no sea nulo
    $this->assertNotNull($response->json('data.email_verified_at'));
});

test('verificación exitosa elimina todos los tokens pendientes del usuario', function () {
    $user = User::factory()->create(['email_verified_at' => null]);

    // Crear múltiples tokens para el mismo usuario
    $verification1 = EmailVerification::createForUser($user);
    $verification2 = EmailVerification::factory()->create([
        'user_id' => $user->id,
        'token' => EmailVerification::generateToken(),
        'expires_at' => now()->addMinutes(60),
    ]);
    $verification3 = EmailVerification::factory()->create([
        'user_id' => $user->id,
        'token' => EmailVerification::generateToken(),
        'expires_at' => now()->addMinutes(60),
    ]);

    // Usar el primer token para verificar
    $response = $this->postJson('/api/auth/verify-email', [
        'token' => $verification1->token,
    ]);

    $response->assertStatus(200);

    // Verificar que todos los tokens del usuario fueron eliminados
    $this->assertDatabaseMissing('email_verifications', [
        'user_id' => $user->id,
    ]);

    // Verificar que el usuario quedó verificado
    $user->refresh();
    $this->assertNotNull($user->email_verified_at);
});

test('sistema elimina tokens expirados automáticamente', function () {
    $user = User::factory()->create(['email_verified_at' => null]);

    // Crear un token expirado
    $expiredVerification = EmailVerification::factory()->expired()->create([
        'user_id' => $user->id,
    ]);

    // Intentar usar el token expirado
    $response = $this->postJson('/api/auth/verify-email', [
        'token' => $expiredVerification->token,
    ]);

    $response->assertStatus(410);

    // Verificar que el token expirado fue eliminado
    $this->assertDatabaseMissing('email_verifications', [
        'token' => $expiredVerification->token,
    ]);
});