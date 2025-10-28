<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\EmailVerification;
use App\Mail\EmailVerificationMail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RegisterController extends Controller
{
    /**
     * Máximo de intentos de registro por hora por IP.
     */
    protected int $maxAttempts = 5;

    /**
     * Tiempo de bloqueo en minutos.
     */
    protected int $decayMinutes = 60;

    /**
     * Register a new user.
     */
    public function register(Request $request): JsonResponse
    {
        $this->ensureIsNotRateLimited($request);

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'rfc' => ['required', 'string', 'between:12,13', 'unique:users', 'regex:/^[A-Za-z&Ññ]{3,4}[0-9]{6}[A-Za-z0-9]{3}$/'],
        ], [
            'rfc.regex' => 'El RFC debe tener un formato válido (12 caracteres para persona moral, 13 para persona física)',
            'rfc.unique' => 'Este RFC ya está registrado en nuestro sistema',
            'email.unique' => 'Este correo electrónico ya está registrado',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Error de validación.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Crear el usuario con el rol 'solicitante' por defecto
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'rfc' => strtoupper($request->rfc),
                'user_type' => 'EXTERNO',
            ]);

            // Asignar rol de solicitante
            $user->assignRole('solicitante');

            // Crear token de verificación
            $verification = EmailVerification::createForUser($user);

            // Enviar email de verificación
            Mail::to($user->email)->send(new EmailVerificationMail($user, $verification->token));

            return response()->json([
                'status' => true,
                'message' => 'Usuario registrado exitosamente. Por favor, verifica tu correo electrónico.',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'rfc' => $user->rfc,
                        'user_type' => $user->user_type,
                        'email_verified_at' => $user->email_verified_at,
                        'created_at' => $user->created_at,
                    ],
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error al registrar el usuario.',
                'errors' => [
                    'general' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Validate RFC format and check against external API (placeholder for future implementation).
     */
    protected function validateRFC($rfc): array
    {
        $rfc = strtoupper($rfc);

        // Validación básica de formato
        if (!preg_match('/^[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}$/', $rfc)) {
            return [
                'valid' => false,
                'message' => 'El RFC no tiene un formato válido.',
                'suggestion' => 'Ejemplo: ABCD123456XYZ (moral) o ABCD12345678XYZ (física)',
            ];
        }

        // TODO: Implementar validación contra API externa del cliente
        // Por ahora, solo validamos el formato

        return [
            'valid' => true,
            'rfc' => $rfc,
        ];
    }

    /**
     * Check if email already exists.
     */
    protected function emailExists(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    /**
     * Check if RFC already exists.
     */
    protected function rfcExists(string $rfc): bool
    {
        return User::where('rfc', $rfc)->exists();
    }

    /**
     * Prevent brute force attacks on registration.
     */
    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey($request), $this->maxAttempts)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => ['Demasiados intentos de registro. Por favor, intenta nuevamente en ' . $seconds . ' segundos.'],
        ]);
    }

    /**
     * Generate throttle key for rate limiting.
     */
    protected function throttleKey(Request $request): string
    {
        return 'register:' . $request->ip();
    }

    /**
     * Get validation rules for user registration.
     */
    protected function getValidationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'rfc' => ['required', 'string', 'between:12,13', 'unique:users', 'regex:/^[A-Za-z&Ññ]{3,4}[0-9]{6}[A-Za-z0-9]{3}$/'],
        ];
    }

    /**
     * Get custom error messages for validation failures.
     */
    protected function getValidationMessages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede exceder los 255 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El formato del correo electrónico no es válido.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'rfc.required' => 'El RFC es obligatorio.',
            'rfc.between' => 'El RFC debe tener entre 12 y 13 caracteres.',
            'rfc.unique' => 'Este RFC ya está registrado en nuestro sistema.',
            'rfc.regex' => 'El RFC debe tener un formato válido (12 caracteres para persona moral, 13 para persona física).',
        ];
    }
}