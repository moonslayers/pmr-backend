<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\EmailVerification;
use App\Mail\EmailVerificationMail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EmailVerificationController extends Controller
{
    /**
     * Máximo de intentos de verificación por hora por IP.
     */
    protected int $maxAttempts = 10;

    /**
     * Tiempo de bloqueo en minutos.
     */
    protected int $decayMinutes = 60;

    /**
     * Verify email with token.
     */
    public function verify(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string', 'size:64'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Token inválido.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $token = $request->token;

        // Find the verification token (first try valid tokens)
        $verification = EmailVerification::findByToken($token);

        // If not found, try to find any token (including expired ones)
        if (!$verification) {
            $verification = EmailVerification::findAnyByToken($token);

            // If we found a token but it's expired
            if ($verification && $verification->isExpired()) {
                // Delete expired token
                $verification->delete();

                return response()->json([
                    'status' => false,
                    'message' => 'El token de verificación ha expirado.',
                    'errors' => [
                        'token' => ['El token ha expirado. Por favor, solicita un nuevo correo de verificación.'],
                    ],
                ], 410);
            }

            // Token was not found at all
            return response()->json([
                'status' => false,
                'message' => 'El token de verificación es inválido o ha expirado.',
                'errors' => [
                    'token' => ['Token no encontrado o expirado.'],
                ],
            ], 404);
        }

        try {
            // Mark user email as verified
            $user = $verification->user;
            $user->email_verified_at = now();
            $user->save();

            // Delete ALL verification records for this user (not just the one used)
            EmailVerification::where('user_id', $user->id)->delete();

            return response()->json([
                'status' => true,
                'message' => 'Correo electrónico verificado exitosamente.',
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
                    'verified_at' => now()->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error al verificar el correo electrónico.',
                'errors' => [
                    'general' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Resend verification email.
     */
    public function resend(Request $request): JsonResponse
    {
        $this->ensureIsNotRateLimited($request);

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.exists' => 'El correo electrónico no está registrado en nuestro sistema.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Correo electrónico inválido.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $email = $request->email;
        $user = User::where('email', $email)->first();

        // Check if user is already verified
        if ($user->email_verified_at) {
            return response()->json([
                'status' => false,
                'message' => 'Este correo electrónico ya ha sido verificado previamente.',
                'errors' => [
                    'email' => ['El correo electrónico ya está verificado.'],
                ],
            ], 422);
        }

        try {
            // Delete any existing unverified tokens for this user
            EmailVerification::where('user_id', $user->id)
                ->whereNull('verified_at')
                ->delete();

            // Create new verification token
            $verification = EmailVerification::createForUser($user);

            // Send verification email
            Mail::to($user->email)->send(new EmailVerificationMail($user, $verification->token));

            return response()->json([
                'status' => true,
                'message' => 'Se ha enviado un nuevo correo de verificación.',
                'data' => [
                    'expires_at' => $verification->expires_at,
                    'email' => $user->email,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error al enviar el correo de verificación.',
                'errors' => [
                    'general' => $e->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Check email verification status.
     */
    public function check(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.exists' => 'El correo electrónico no está registrado en nuestro sistema.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Correo electrónico inválido.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        $isVerified = !is_null($user->email_verified_at);
        $hasPendingVerification = EmailVerification::where('user_id', $user->id)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->exists();

        return response()->json([
            'status' => true,
            'data' => [
                'email' => $user->email,
                'is_verified' => $isVerified,
                'has_pending_verification' => $hasPendingVerification,
                'email_verified_at' => $user->email_verified_at,
            ],
        ]);
    }

    /**
     * Prevent brute force attacks on verification.
     */
    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey($request), $this->maxAttempts)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => ['Demasiados intentos de verificación. Por favor, intenta nuevamente en ' . $seconds . ' segundos.'],
        ]);
    }

    /**
     * Generate throttle key for rate limiting.
     */
    protected function throttleKey(Request $request): string
    {
        return 'email-verify:' . $request->ip();
    }
}