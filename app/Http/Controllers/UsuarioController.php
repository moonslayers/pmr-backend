<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Services\Verifiers;

class UsuarioController extends SuperController
{
    private $verifiers;
    protected $validationResult;

    /**
     * Constructor que configura el controlador con el modelo User
     * y define las reglas de validación para las operaciones CRUD. Esto es para una prueba
     */
    public function __construct()
    {
        parent::__construct(User::class);
        $this->verifiers = new Verifiers();

        $this->mainRelations=[
            'empresa',
            'roles.permissions',
            'permissions'
        ];

        // Reglas de validación para crear usuarios
        $this->rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'rfc' => 'required|string|between:12,13|unique:users,rfc',
            'user_type' => 'required|in:INTERNO,EXTERNO',
            'role' => 'required|string|exists:roles,name',
        ];

        // Reglas de validación para actualizar usuarios
        $this->rules_update = [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email',
            'password' => 'sometimes|string|min:8',
            'rfc' => 'sometimes|string|between:12,13|unique:users,rfc',
            'user_type' => 'sometimes|in:INTERNO,EXTERNO',
            'role' => 'sometimes|string|exists:roles,name',
        ];

        // Columnas que se deben excluir de la búsqueda genérica
        $this->excludedColumnsInSearch = ['id', 'created_at', 'deleted_at', 'created_by', 'updated_at', 'password', 'remember_token'];

        // No requerir usuario_id para la creación (usuarios pueden crearse sin estar autenticados)
        $this->useUsuarioId = false;
    }

    /**
     * Validación previa a la inserción de usuarios
     * - Aplica hash al password antes de guardar
     * - Separa el rol para asignarlo después de crear el usuario
     */
    public function storeValidacionPreInsercion(array $data, bool $insercion_unica)
    {
        // Extraer rol antes de crear el usuario y guardarlo en temporal
        $role = $data['role'] ?? null;
        unset($data['role']); // No guardar el rol directamente en el usuario

        // Guardar el rol temporalmente en una propiedad para usarlo después
        $this->validationResult = ['role' => $role];

        // Si se incluye password, aplicar hash
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return [
            "status" => true,
            "message" => "",
            "code" => 200,
            'data' => $data
        ];
    }

    /**
     * Validación previa a la actualización de usuarios
     * - Aplica hash al password si se está actualizando
     * - Separa el rol para asignarlo después de actualizar el usuario
     */
    public function updateValidacionPreInsercion(array $data, bool $insercion_unica)
    {

        // Extraer rol antes de actualizar el usuario y guardarlo en temporal
        $role = $data['role'] ?? null;
        unset($data['role']); // No guardar el rol directamente en el usuario

        // Guardar el rol temporalmente en una propiedad para usarlo después
        $this->validationResult = ['role' => $role];

        // Si se incluye password, aplicar hash
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return [
            "status" => true,
            "message" => "",
            "code" => 200,
            'data' => $data
        ];
    }

    /**
     * Verificación previa a la actualización de usuarios
     * - Protege a usuarios con rol admin-sistema de ser modificados por otros admin-sistema
     * - Restringe la modificación de usuarios según permisos
     * - Restringe la modificación de contraseñas según permisos
     * - Aplica hash al password si se está actualizando
     */
    public function verificacionPreActualizacion($id, $updateData)
    {
        $userToUpdate = $this->model::find($id);
        $authenticatedUser = auth()->user();

        if (!$userToUpdate || !$authenticatedUser) {
            return [
                "status" => false,
                "message" => "Usuario no encontrado",
                "code" => 404
            ];
        }

        $mismoUsuario = $authenticatedUser->id == $id;
        $tienePermisoEditar = $authenticatedUser->can('usuarios.editar.any');

        // 1. Verificar permiso para editar otros usuarios
        if (!$mismoUsuario && !$tienePermisoEditar) {
            return [
                "status" => false,
                "message" => "No tienes permiso para modificar otros usuarios. Permiso requerido: usuarios.editar.any",
                "code" => 403
            ];
        }

        // 2. Protección de usuarios admin-sistema - solo otros admin-sistema pueden modificarlos
        if ($userToUpdate->hasRole('admin-sistema') && !$authenticatedUser->hasRole('admin-sistema')) {
            return [
                "status" => false,
                "message" => "No se permite modificar la información del usuario administrador del sistema",
                "code" => 403
            ];
        }

        // 3. Restricción de contraseña - requiere permiso usuarios.password.cambiar o ser el mismo usuario
        if (isset($updateData['password'])) {
            // Permitir si el usuario es el mismo
            $tienePermisoPassword = $authenticatedUser->can('usuarios.password.cambiar');

            if (!$mismoUsuario && !$tienePermisoPassword) {
                return [
                    "status" => false,
                    "message" => "No se permite modificar la contraseña de otro usuario. Permiso requerido: usuarios.password.cambiar",
                    "code" => 403
                ];
            }

            // Aplicar hash al password
            $updateData['password'] = Hash::make($updateData['password']);
        }

        // Permitir la actualización para otros casos
        return ["status" => true, "message" => "", "code" => 200, 'data' => $updateData];
    }

    /**
     * Acciones posteriores a la inserción de usuarios
     * - Asigna el rol especificado al usuario creado
     */
    public function storePostInsercionAcciones(array $datos_insertados, bool $insercion_unica)
    {
        // Obtener el rol de los datos de validación
        $role = $this->validationResult['role'] ?? null;

        // Debug temporal
        if (app()->environment('testing')) {
            \Log::info('Role assignment attempt', [
                'role' => $role,
                'user_id' => $datos_insertados['id'] ?? null,
                'validation_result' => $this->validationResult
            ]);
        }

        if ($role && $insercion_unica) {
            // Para inserción única
            $user = $this->model::find($datos_insertados['id']);
            if ($user) {
                $user->assignRole($role);
            }
        } elseif (!$insercion_unica) {
            // Para inserciones múltiples (array de usuarios)
            foreach ($datos_insertados as $userData) {
                if (isset($userData['id'])) {
                    $user = $this->model::find($userData['id']);
                    if ($user) {
                        // Para inserciones múltiples, asignamos rol por defecto 'solicitante'
                        $user->assignRole('solicitante');
                    }
                }
            }
        }

        return response()->json(["status" => true, "message" => "Usuario creado y rol asignado correctamente"]);
    }

    /**
     * Acciones posteriores a la actualización de usuarios
     * - Actualiza el rol si se especificó uno nuevo
     */
    public function updatePostAccion($refreshedData, $oldData)
    {
        // Obtener el rol de los datos de validación
        $role = $this->validationResult['role'] ?? null;

        if ($role && isset($refreshedData['id'])) {
            /**
             * @var User
             */
            $user = $this->model::find($refreshedData['id']);
            if ($user) {
                // Remover todos los roles actuales y asignar el nuevo
                $user->syncRoles([$role]);
            }
        }

        return response()->json([
            'status' => true,
            'message' => "Usuario actualizado y rol asignado correctamente"
        ], status: 200);
    }
}