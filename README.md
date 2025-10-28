# PMR

![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![RESTful API](https://img.shields.io/badge/RESTful-API-green?style=for-the-badge)

PMR (Sistema Integral de Gestión de Información) - Backend API Laravel para autenticación, gestión de usuarios y control de acceso basado en roles.

## 📋 Características Principales

- **Autenticación Segura**: Sistema de login/logout con tokens Laravel Sanctum
- **Gestión de Usuarios**: Registro, perfil y administración de usuarios
- **Verificación de Email**: Confirmación segura de cuentas de usuario
- **Control de Acceso**: Sistema de roles y permisos con Spatie Laravel Permission
- **API RESTful**: Interfaz completa para autenticación y gestión de usuarios
- **Arquitectura Limpia**: Base sólida para nuevos desarrollos

## 🚀 Tecnologías Utilizadas

- **Laravel 12** - Framework PHP
- **PHP 8.2+** - Lenguaje de programación
- **MySQL** - Base de datos relacional
- **Laravel Sanctum** - Autenticación API con tokens
- **Spatie Laravel Permission** - Sistema de roles y permisos
- **Pest PHP** - Framework de testing
- **Vite + Tailwind CSS v4** - Build de assets

## 📁 Estructura del Proyecto

```markdown
pmr-backend/
├── app/
│   ├── Http/Controllers/     # Controladores de autenticación y usuarios
│   ├── Models/              # Modelos de usuario y verificación
│   └── Services/            # Servicios de validación
├── database/
│   ├── migrations/          # Migraciones de base de datos
│   └── seeders/            # Datos de prueba
├── routes/
│   ├── api.php             # Rutas API de autenticación
│   └── web.php             # Rutas web
├── tests/                  # Tests de autenticación y usuarios
└── README.md
```

## ⚙️ Instalación y Configuración

### Prerrequisitos

- PHP 8.2+
- Composer
- MySQL 8.0+
- Node.js (para assets)

### Pasos de Instalación

1. **Clonar el repositorio**

```bash
git clone https://github.com/tu-usuario/pmr-backend.git
cd pmr-backend
```

2. **Instalar dependencias**

```bash
composer install
npm install
```

3. **Configurar entorno**

```bash
cp .env.example .env
php artisan key:generate
```

4. **Configurar base de datos**

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pmr
DB_USERNAME=root
DB_PASSWORD=
```

5. **Ejecutar migraciones**

```bash
php artisan migrate --seed
```

6. **Construir assets**

```bash
npm run build
```

### Configuración Rápida

```bash
# Configuración completa del proyecto
composer run setup

# Iniciar servidor de desarrollo
composer run dev
```

## 🎯 Uso de la API

### Autenticación

La API utiliza token-based authentication mediante Laravel Sanctum.

#### Registro de Usuario
```bash
POST /api/auth/register
{
    "name": "Nombre Usuario",
    "email": "usuario@ejemplo.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

#### Login
```bash
POST /api/auth/login
{
    "email": "usuario@ejemplo.com",
    "password": "password123"
}
```

#### Respuesta de Autenticación
```json
{
    "token": "1|abc123def456...",
    "user": {
        "id": 1,
        "name": "Nombre Usuario",
        "email": "usuario@ejemplo.com",
        "roles": ["Usuario-externo"]
    }
}
```

#### Verificación de Email
```bash
POST /api/auth/verify-email
{
    "email": "usuario@ejemplo.com",
    "code": "123456"
}
```

### Endpoints Principales

- `POST /api/auth/register` - Registro de usuarios
- `POST /api/auth/login` - Inicio de sesión
- `POST /api/auth/logout` - Cierre de sesión
- `GET /api/auth/me` - Información del usuario actual
- `POST /api/auth/verify-email` - Verificar email
- `POST /api/auth/resend-verification` - Reenviar código de verificación
- `GET /api/users` - Listar usuarios (admin)
- `PUT /api/users/{id}` - Actualizar usuario
- `POST /api/users/{id}/assign-role` - Asignar rol (admin)

### Roles del Sistema

- **Admin-sistema**: Super administrador
- **Admin-general**: Administrador general
- **Usuario-interno**: Usuario interno del sistema
- **Usuario-externo**: Usuario externo por defecto

## 🤝 Contribuir

¡Agradecemos las contribuciones! Por favor, lee nuestra guía de contribuciones antes de enviar pull requests.

[**📖 Ver Guía de Contribución**](./CONTRIBUTING.md)

### Flujo de Trabajo

1. Fork el proyecto
2. Crear una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abrir un Pull Request

## 🧪 Testing

```bash
# Ejecutar todos los tests
composer run test

# Ejecutar tests directamente
php artisan test

# Ejecutar tests de autenticación
php artisan test tests/Feature/LoginTest.php

# Ejecutar tests con cobertura
php artisan test --coverage
```

## 🔧 Desarrollo

### Comandos Útiles

```bash
# Formato de código (Laravel Pint)
./vendor/bin/pint

# Verificar estilo de código
./vendor/bin/pint --test

# Crear nueva migración
php artisan make:migration create_table_name

# Generar nueva clave de aplicación
php artisan key:generate
```

### Estándares de Código

- **PSR-4** para autoloading
- **Nombres en inglés** para variables y funciones
- **Documentación en español** para comentarios
- **PHPDoc obligatorio** para todos los métodos

## 📄 Licencia

Este proyecto está bajo la Licencia MIT - ver el archivo [LICENSE](LICENSE) para más detalles.

## 📞 Soporte

Si tienes alguna pregunta o problema, por favor:

1. Revisa la documentación del proyecto
2. Abre un issue en el repositorio
3. Contacta al equipo de desarrollo

---

**PMR** - Sistema Integral de Gestión de Información © 2024
