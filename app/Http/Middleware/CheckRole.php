<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string[]  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Verificar que el usuario esté autenticado
        if (!Auth::check()) {
            return response()->json([
                'status' => false,
                'message' => 'No autenticado. Debes iniciar sesión para acceder a este recurso.'
            ], 401);
        }

        $user = Auth::user();

        // Verificar que el usuario tenga alguno de los roles especificados
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        return response()->json([
            'status' => false,
            'message' => 'Acceso denegado. No tienes los permisos necesarios para acceder a este recurso.',
            'required_roles' => $roles,
            'current_role' => $user->getRoleNames()->first() ?? 'none'
        ], 403);
    }
}
