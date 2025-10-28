<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class LoginController extends Controller
{
    /**
     * Intento máximo de login por minuto.
     */
    protected int $maxAttempts = 5;

    /**
     * Tiempo de bloqueo en segundos.
     */
    protected int $decayMinutes = 1;

    /**
     * Login de usuario y creación de token.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        $this->ensureIsNotRateLimited($request);

        $request->validate([
            'rfc' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('rfc', $request->rfc)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($this->throttleKey($request), $this->decayMinutes * 60);

            throw ValidationException::withMessages([
                'rfc' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));

        // Revocar tokens anteriores del usuario (opcional - para login único)
        $user->tokens()->delete();

        // Crear nuevo token
        $token = $user->createToken('auth_token', ['*'], now()->addMinutes(
            (int) config('sanctum.expiration', 1440)
        ));

        return response()->json([
            'message' => 'Login exitoso.',
            'user' => $user,
            'roles' => $user->getRoleNames(), // Devuelve una colección de nombres de roles
            'permissions' => $user->getAllPermissions()->pluck('name'), // Devuelve una colección de nombres de permisos
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at,
            'expires_in_minutes' => config('sanctum.expiration', 1440),
        ]);
    }

    /**
     * Logout del usuario (revoca todos sus tokens).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'No hay usuario autenticado.',
            ], 401);
        }

        // Revocar todos los tokens del usuario
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente.',
        ]);
    }

    /**
     * Refrescar el token del usuario (crea uno nuevo y revoca el anterior).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'No hay usuario autenticado.',
            ], 401);
        }

        // Obtener token actual
        $currentToken = $request->bearerToken();
        $tokenInstance = $user->tokens()
            ->where('token', hash('sha256', $currentToken))
            ->first();

        if (!$tokenInstance) {
            return response()->json([
                'message' => 'Token inválido.',
            ], 401);
        }

        // Revocar token actual
        $tokenInstance->delete();

        // Crear nuevo token
        $newToken = $user->createToken('auth_token', ['*'], now()->addMinutes(
            (int) config('sanctum.expiration', 1440)
        ));

        return response()->json([
            'message' => 'Token refrescado exitosamente.',
            'user' => $user,
            'token' => $newToken->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $newToken->accessToken->expires_at,
            'expires_in_minutes' => config('sanctum.expiration', 1440),
        ]);
    }

    /**
     * Obtener datos del usuario autenticado.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'No hay usuario autenticado.',
            ], 401);
        }

        // Cargar tokens activos del usuario
        $user->tokens;

        return response()->json([
            'user' => $user,
            'message' => 'Usuario autenticado.',
        ]);
    }

    /**
     * Verificar si el token es válido (sin modificar expiración).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function check(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'valid' => false,
                'message' => 'Token inválido o expirado.',
            ], 401);
        }

        // Obtener token actual para verificar expiración
        $currentToken = $request->bearerToken();
        $tokenInstance = $user->tokens()
            ->where('token', hash('sha256', $currentToken))
            ->first();

        if (!$tokenInstance) {
            return response()->json([
                'valid' => false,
                'message' => 'Token no encontrado.',
            ], 401);
        }

        $isExpired = $tokenInstance->expires_at && $tokenInstance->expires_at->isPast();

        return response()->json([
            'valid' => !$isExpired,
            'expires_at' => $tokenInstance->expires_at,
            'expires_in_minutes' => $tokenInstance->expires_at
                ? max(0, $tokenInstance->expires_at->diffInMinutes(now()))
                : null,
            'message' => $isExpired ? 'Token expirado.' : 'Token válido.',
        ], $isExpired ? 401 : 200);
    }

    /**
     * Evitar ataques de fuerza bruta.
     *
     * @param Request $request
     * @return void
     * @throws ValidationException
     */
    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey($request), $this->maxAttempts)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'rfc' => ['Demasiados intentos de login. Por favor, intenta nuevamente en ' . $seconds . ' segundos.'],
        ]);
    }

    /**
     * Generar clave para rate limiting.
     *
     * @param Request $request
     * @return string
     */
    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input('rfc')) . '|' . $request->ip());
    }
}