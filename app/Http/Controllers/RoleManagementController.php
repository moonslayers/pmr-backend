<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleManagementController extends Controller
{
    /**
     * Obtener todos los roles disponibles en el sistema.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $roles = Role::all()->map(function ($role) {
            return [
                'name' => $role->name,
                'display_name' => $this->getRoleDisplayName($role->name),
                'description' => $this->getRoleDescription($role->name),
                'permissions_count' => $role->permissions->count(),
                'created_at' => $role->created_at,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Roles obtenidos exitosamente',
            'data' => $roles
        ]);
    }

    /**
     * Obtener todos los permisos asociados a un rol específico.
     *
     * @param string $roleName
     * @return JsonResponse
     */
    public function permissions(string $roleName): JsonResponse
    {
        $role = Role::where('name', $roleName)->first();

        if (!$role) {
            return response()->json([
                'status' => false,
                'message' => 'Rol no encontrado',
                'errors' => [
                    'role' => ['El rol especificado no existe']
                ]
            ], 404);
        }

        $permissions = $role->permissions->map(function ($permission) {
            return [
                'name' => $permission->name,
                'display_name' => $this->getPermissionDisplayName($permission->name),
                'description' => $this->getPermissionDescription($permission->name),
                'module' => $this->getPermissionModule($permission->name),
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Permisos del rol obtenidos exitosamente',
            'data' => [
                'role' => [
                    'name' => $role->name,
                    'display_name' => $this->getRoleDisplayName($role->name),
                    'description' => $this->getRoleDescription($role->name),
                ],
                'permissions' => $permissions
            ]
        ]);
    }

    /**
     * Asignar un rol a un usuario existente.
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function assignRole(Request $request, int $userId): JsonResponse
    {
        $request->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Usuario no encontrado',
                'errors' => [
                    'user' => ['El usuario especificado no existe']
                ]
            ], 404);
        }

        $authenticatedUser = $request->user();

        // Validar que el usuario autenticado sea admin-sistema
        if (!$authenticatedUser->hasRole('admin-sistema')) {
            return response()->json([
                'status' => false,
                'message' => 'Acceso denegado',
                'errors' => [
                    'authorization' => ['Solo los administradores del sistema pueden asignar roles']
                ]
            ], 403);
        }

        // Evitar que un usuario admin-sistema modifique su propio rol
        if ($authenticatedUser->id === $user->id && $request->role !== 'admin-sistema') {
            return response()->json([
                'status' => false,
                'message' => 'Operación no permitida',
                'errors' => [
                    'self_modification' => ['No puedes modificar tu propio rol de administrador del sistema']
                ]
            ], 403);
        }

        $newRole = $request->role;
        $previousRoles = $user->getRoleNames()->toArray();

        try {
            DB::beginTransaction();

            // Remover todos los roles actuales y asignar el nuevo
            $user->syncRoles([$newRole]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Rol asignado exitosamente',
                'data' => [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'assigned_role' => $newRole,
                    'previous_roles' => $previousRoles,
                    'assigned_at' => now()->toDateTimeString(),
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Error al asignar el rol',
                'errors' => [
                    'database' => ['Ocurrió un error en la base de datos: ' . $e->getMessage()]
                ]
            ], 500);
        }
    }

    /**
     * Obtener todos los roles disponibles para asignar (con información básica).
     * Este endpoint es más ligero y está diseñado para selects/dropdowns.
     *
     * @return JsonResponse
     */
    public function availableRoles(): JsonResponse
    {
        $roles = Role::all()->map(function ($role) {
            return [
                'value' => $role->name,
                'label' => $this->getRoleDisplayName($role->name),
                'description' => $this->getRoleDescription($role->name),
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Roles disponibles obtenidos exitosamente',
            'data' => $roles
        ]);
    }

    /**
     * Obtener roles actuales de un usuario específico.
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function userRoles(int $userId): JsonResponse
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Usuario no encontrado',
                'errors' => [
                    'user' => ['El usuario especificado no existe']
                ]
            ], 404);
        }

        $roles = $user->getRoleNames()->map(function ($roleName) {
            return [
                'name' => $roleName,
                'display_name' => $this->getRoleDisplayName($roleName),
                'description' => $this->getRoleDescription($roleName),
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Roles del usuario obtenidos exitosamente',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'roles' => $roles,
            ]
        ]);
    }

    /**
     * Obtener nombre descriptivo para un rol.
     *
     * @param string $roleName
     * @return string
     */
    private function getRoleDisplayName(string $roleName): string
    {
        $displayNames = [
            'admin-sistema' => 'Administrador del Sistema',
            'admin-general' => 'Administrador General',
            'usuario-sei' => 'Usuario SEI',
            'solicitante' => 'Solicitante',
        ];

        return $displayNames[$roleName] ?? $roleName;
    }

    /**
     * Obtener descripción para un rol.
     *
     * @param string $roleName
     * @return string
     */
    private function getRoleDescription(string $roleName): string
    {
        $descriptions = [
            'admin-sistema' => 'Acceso completo al sistema incluyendo gestión de usuarios y configuración crítica',
            'admin-general' => 'Acceso administrativo para gestión de contenido y usuarios (sin configuración crítica)',
            'usuario-sei' => 'Acceso para operaciones diarias de SEI: clasificación, acciones y seguimiento de solicitudes',
            'solicitante' => 'Acceso básico para crear y gestionar solicitudes propias',
        ];

        return $descriptions[$roleName] ?? 'Rol sin descripción definida';
    }

    /**
     * Obtener nombre descriptivo para un permiso.
     *
     * @param string $permissionName
     * @return string
     */
    private function getPermissionDisplayName(string $permissionName): string
    {
        $displayNames = [
            'usuarios.ver.all' => 'Ver todos los usuarios',
            'usuarios.crear.any' => 'Crear cualquier usuario',
            'usuarios.editar.any' => 'Editar cualquier usuario',
            'usuarios.eliminar.any' => 'Eliminar cualquier usuario',
            'usuarios.password.cambiar' => 'Cambiar contraseñas',
            'solicitudes.ver.all' => 'Ver todas las solicitudes',
            'solicitudes.clasificar' => 'Clasificar solicitudes',
            'solicitudes.asignar' => 'Asignar solicitudes',
            'solicitudes.en.proceso.ver' => 'Ver solicitudes en proceso',
            'catalogos.crud' => 'Gestionar catálogos',
            'acciones.crud' => 'Gestionar acciones',
            'comentarios.crud' => 'Gestionar comentarios',
            'evidencias.descargar' => 'Descargar evidencias',
            'evidencias.ver' => 'Ver evidencias',
        ];

        return $displayNames[$permissionName] ?? $permissionName;
    }

    /**
     * Obtener descripción para un permiso.
     *
     * @param string $permissionName
     * @return string
     */
    private function getPermissionDescription(string $permissionName): string
    {
        $descriptions = [
            'usuarios.ver.all' => 'Permite ver la lista completa de usuarios del sistema',
            'usuarios.crear.any' => 'Permite crear nuevos usuarios con cualquier rol',
            'usuarios.editar.any' => 'Permite editar datos de cualquier usuario',
            'usuarios.eliminar.any' => 'Permite eliminar usuarios del sistema',
            'usuarios.password.cambiar' => 'Permite cambiar contraseñas de otros usuarios',
            'solicitudes.ver.all' => 'Permite ver todas las solicitudes del sistema',
            'solicitudes.clasificar' => 'Permite clasificar solicitudes pendientes',
            'solicitudes.asignar' => 'Permite asignar solicitudes a usuarios',
            'solicitudes.en.proceso.ver' => 'Permite ver solicitudes que están en proceso',
            'catalogos.crud' => 'Permite crear, editar, eliminar catálogos del sistema',
            'acciones.crud' => 'Permite gestionar acciones de solicitudes',
            'comentarios.crud' => 'Permite crear, editar, eliminar comentarios',
            'evidencias.descargar' => 'Permite descargar archivos de evidencia',
            'evidencias.ver' => 'Permite visualizar archivos de evidencia',
        ];

        return $descriptions[$permissionName] ?? 'Permiso sin descripción definida';
    }

    /**
     * Obtener módulo al que pertenece un permiso.
     *
     * @param string $permissionName
     * @return string
     */
    private function getPermissionModule(string $permissionName): string
    {
        if (str_starts_with($permissionName, 'usuarios.')) {
            return 'Gestión de Usuarios';
        }
        if (str_starts_with($permissionName, 'solicitudes.')) {
            return 'Gestión de Solicitudes';
        }
        if (str_starts_with($permissionName, 'catalogos.')) {
            return 'Catálogos';
        }
        if (str_starts_with($permissionName, 'acciones.')) {
            return 'Acciones';
        }
        if (str_starts_with($permissionName, 'comentarios.')) {
            return 'Comentarios';
        }
        if (str_starts_with($permissionName, 'evidencias.')) {
            return 'Evidencias';
        }

        return 'General';
    }
}