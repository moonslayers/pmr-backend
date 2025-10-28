<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckInternalUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Verificar que el usuario esté autenticado
        if (!Auth::check()) {
            return response()->json([
                'status' => false,
                'message' => 'No autenticado. Debes iniciar sesión para acceder a este recurso.'
            ], 401);
        }

        // Verificar que el usuario sea de tipo INTERNO
        $user = Auth::user();
        if ($user->user_type !== 'INTERNO') {
            return response()->json([
                'status' => false,
                'message' => 'Acceso denegado. Este recurso está disponible solo para usuarios internos.'
            ], 403);
        }

        return $next($request);
    }
}