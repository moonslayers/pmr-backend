# 📚 Documentación API - Sistema de Autenticación

## 🔐 Módulo de Autenticación

Controlador: `LoginController`  
Namespace: `App\Http\Controllers`  
Base Path: `/api/auth`

---

## 📋 Endpoints de Autenticación

### 🔑 Login de Usuario

**POST** `/api/auth/login`

Autentica un usuario en el sistema y genera un token de acceso.

#### 🛡️ Seguridad

- Rate Limiting: 5 intentos por minuto
- Bloqueo temporal tras exceder límites
- Validación de credenciales

#### 📥 Request

**Headers:**

```http
Content-Type: application/json
Accept: application/json
```

**Body:**

```json
{
  "email": "usuario@empresa.com",
  "password": "contraseña123"
}
```

**Validación:**

| Campo | Reglas | Descripción |
|-------|--------|-------------|
| email | `required|string|email` | Email del usuario |
| password | `required|string` | Contraseña del usuario |

#### 📤 Response

**✅ 200 - Login Exitoso**

```json
{
  "message": "Login exitoso.",
  "user": {
    "id": 1,
    "name": "Juan Pérez",
    "email": "juan@empresa.com",
    "role": "admin",
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T10:30:00Z"
  },
  "token": "1|a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6",
  "token_type": "Bearer",
  "expires_at": "2024-01-16T10:30:00Z",
  "expires_in_minutes": 1440
}
```

**❌ 422 - Error de Validación**

```json
{
  "message": "Los datos proporcionados no son válidos.",
  "errors": {
    "email": ["El campo email es obligatorio."],
    "password": ["El campo password es obligatorio."]
  }
}
```

**❌ 429 - Demasiados Intentos**

```json
{
  "message": "Demasiados intentos de login.",
  "errors": {
    "email": ["Demasiados intentos de login. Por favor, intenta nuevamente en 45 segundos."]
  }
}
```

---

### 🚪 Logout de Usuario

**POST** `/api/auth/logout`

Cierra la sesión del usuario revocando todos sus tokens.

#### 🔐 Autenticación Requerida

```http
Authorization: Bearer {token}
```

#### 📤 Response

**✅ 200 - Logout Exitoso**

```json
{
  "message": "Sesión cerrada exitosamente."
}
```

**❌ 401 - No Autenticado**

```json
{
  "message": "No hay usuario autenticado."
}
```

---

### 🔄 Refresh Token

**POST** `/api/auth/refresh`

Renueva el token de acceso creando uno nuevo y revocando el anterior.

#### 🔐 Autenticación Requerida

```http
Authorization: Bearer {token}
```

#### 📤 Response

**✅ 200 - Token Refrescado**

```json
{
  "message": "Token refrescado exitosamente.",
  "user": {
    "id": 1,
    "name": "Juan Pérez",
    "email": "juan@empresa.com",
    "role": "admin"
  },
  "token": "2|b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7",
  "token_type": "Bearer",
  "expires_at": "2024-01-16T11:30:00Z",
  "expires_in_minutes": 1440
}
```

**❌ 401 - Token Inválido**

```json
{
  "message": "Token inválido."
}
```

---

### 👤 Obtener Usuario Actual

**GET** `/api/auth/me`

Obtiene la información del usuario autenticado.

#### 🔐 Autenticación Requerida

```http
Authorization: Bearer {token}
```

#### 📤 Response

**✅ 200 - Usuario Obtenido**

```json
{
  "user": {
    "id": 1,
    "name": "Juan Pérez",
    "email": "juan@empresa.com",
    "role": "admin",
    "email_verified_at": "2024-01-15T10:30:00Z",
    "created_at": "2024-01-15T10:30:00Z",
    "updated_at": "2024-01-15T10:30:00Z",
    "tokens": [
      {
        "id": 1,
        "name": "auth_token",
        "abilities": ["*"],
        "expires_at": "2024-01-16T10:30:00Z"
      }
    ]
  },
  "message": "Usuario autenticado."
}
```

**❌ 401 - No Autenticado**

```json
{
  "message": "No hay usuario autenticado."
}
```

---

### ✅ Verificar Token

**GET** `/api/auth/check`

Verifica si el token actual es válido sin modificar su expiración.

#### 🔐 Autenticación Requerida

```http
Authorization: Bearer {token}
```

#### 📤 Response

**✅ 200 - Token Válido**

```json
{
  "valid": true,
  "expires_at": "2024-01-16T10:30:00Z",
  "expires_in_minutes": 1435,
  "message": "Token válido."
}
```

**❌ 401 - Token Inválido/Expirado**

```json
{
  "valid": false,
  "expires_at": "2024-01-15T10:30:00Z",
  "expires_in_minutes": 0,
  "message": "Token expirado."
}
```

---

## 🛡️ Características de Seguridad

### 🔒 Rate Limiting

- **Máximo de intentos**: 5 por minuto
- **Tiempo de bloqueo**: 1 minuto
- **Clave de throttling**: `email|ip_address`

### 🔑 Gestión de Tokens

- **Tipo**: Bearer Token (Laravel Sanctum)
- **Expiración**: Configurable (default: 1440 minutos - 24 horas)
- **Alcance**: Todos los permisos (`*`)
- **Login único**: Revoca tokens anteriores al hacer login

### 🎯 Comportamientos Especiales

#### Login

1. Valida rate limiting
2. Verifica credenciales
3. Revoca tokens existentes (login único)
4. Genera nuevo token con expiración
5. Retorna datos del usuario y token

#### Logout

1. Revoca todos los tokens del usuario
2. Invalida sesión actual

#### Refresh

1. Valida token actual
2. Revoca token actual
3. Genera nuevo token
4. Mantiene datos de usuario

---

## 💻 Ejemplos de Uso

### 🔑 Login con cURL

```bash
curl -X POST "http://localhost:8000/api/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "admin@sagem.com",
    "password": "password123"
  }'
```

### 🔄 Refresh Token con cURL

```bash
curl -X POST "http://localhost:8000/api/auth/refresh" \
  -H "Authorization: Bearer 1|a1b2c3d4e5f6g7h8i9j0" \
  -H "Accept: application/json"
```

### 👤 Obtener Usuario con cURL

```bash
curl -X GET "http://localhost:8000/api/auth/me" \
  -H "Authorization: Bearer 1|a1b2c3d4e5f6g7h8i9j0" \
  -H "Accept: application/json"
```

### 🚪 Logout con cURL

```bash
curl -X POST "http://localhost:8000/api/auth/logout" \
  -H "Authorization: Bearer 1|a1b2c3d4e5f6g7h8i9j0" \
  -H "Accept: application/json"
```

### 🔍 Verificar Token con cURL

```bash
curl -X GET "http://localhost:8000/api/auth/check" \
  -H "Authorization: Bearer 1|a1b2c3d4e5f6g7h8i9j0" \
  -H "Accept: application/json"
```

---

## 📝 Notas Importantes

1. **Tokens de Acceso**: Cada token tiene una expiración configurada en `config/sanctum.php`
2. **Login Único**: Al hacer login se revocan todos los tokens anteriores del usuario
3. **Seguridad**: Implementa rate limiting para prevenir ataques de fuerza bruta
4. **Refresh Token**: Permite renovar el token sin necesidad de re-autenticarse
5. **Verificación**: El endpoint `check` permite verificar la validez del token sin afectar su expiración

---

## 🔄 Flujo Recomendado

1. **Login inicial** → Obtener token
2. **Usar token** en requests subsiguientes
3. **Verificar token** periódicamente con `/check`
4. **Refrescar token** cuando esté próximo a expirar
5. **Logout** cuando se termine la sesión
