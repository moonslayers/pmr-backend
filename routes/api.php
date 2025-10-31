<?php

use App\Http\Controllers\UnidadesAdministrativasController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\RoleManagementController;

/*
|--------------------------------------------------------------------------
| API Routes - PMR (Sistema Integral de Gestión de Información)
|--------------------------------------------------------------------------
|
| Rutas para autenticación, registro y gestión de usuarios
|
*/

// Rutas públicas de autenticación
Route::prefix('auth')->group(function () {
    Route::post('/login', [LoginController::class, 'login']);
});

// Rutas públicas de registro (sin autenticación)
Route::prefix('auth')->group(function () {
    Route::post('/register', [RegisterController::class, 'register']);
    Route::post('/verify-email', [EmailVerificationController::class, 'verify']);
    Route::post('/resend-verification', [EmailVerificationController::class, 'resend']);
    Route::post('/check-verification', [EmailVerificationController::class, 'check']);
});

// Rutas protegidas que requieren autenticación
Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::post('/refresh', [LoginController::class, 'refresh']);
    Route::get('/me', [LoginController::class, 'me']);
    Route::get('/check', [LoginController::class, 'check']);
});

// Ruta de ejemplo protegida
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rutas para gestión de usuarios
Route::middleware(['auth:sanctum', 'permission:usuarios.crear.any'])->post('/usuarios', [UsuarioController::class, 'store']);
Route::middleware(['auth:sanctum', 'permission:usuarios.ver.all'])->get('/usuarios', [UsuarioController::class, 'index']);
Route::middleware('auth:sanctum')->get('/usuarios/{id}', [UsuarioController::class, 'show']);
Route::middleware('auth:sanctum')->put('/usuarios/{id}', [UsuarioController::class, 'update']);
Route::middleware('auth:sanctum')->patch('/usuarios/{id}', [UsuarioController::class, 'update']);
Route::middleware(['auth:sanctum', 'permission:usuarios.eliminar.any'])->delete('/usuarios/{id}', [UsuarioController::class, 'destroy']);

// UE - Datos Iniciales
Route::middleware(['auth:sanctum'])->get('/unidades-administrativas', [UnidadesAdministrativasController::class, 'index']);
// UE - Propuestas
Route::middleware(['auth:sanctum'])->get('/propuestas', [\App\Http\Controllers\PropuestasController::class, 'index']);

// Rutas para gestión de roles (Solo administradores)
Route::middleware(['auth:sanctum', 'permission:usuarios.editar.any'])->prefix('roles')->group(function () {
    Route::get('/', [RoleManagementController::class, 'index']);
    Route::get('/available', [RoleManagementController::class, 'availableRoles']);
    Route::get('/{roleName}/permissions', [RoleManagementController::class, 'permissions']);
});

Route::middleware(['auth:sanctum', 'permission:usuarios.editar.any'])->prefix('usuarios/{userId}')->group(function () {
    Route::post('/assign-role', [RoleManagementController::class, 'assignRole']);
    Route::get('/roles', [RoleManagementController::class, 'userRoles']);
});