<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string[]  ...$permissions
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        // Verificar que el usuario esté autenticado
        if (!Auth::check()) {
            return response()->json([
                'status' => false,
                'message' => 'No autenticado. Debes iniciar sesión para acceder a este recurso.'
            ], 401);
        }

        $user = Auth::user();

        // Verificar que el usuario tenga alguno de los permisos especificados
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return $next($request);
            }
        }

        return response()->json([
            'status' => false,
            'message' => 'Acceso denegado. No tienes los permisos necesarios para realizar esta acción.',
            'required_permissions' => $permissions,
            'current_permissions' => $user->getAllPermissions()->pluck('name')->toArray()
        ], 403);
    }
}
