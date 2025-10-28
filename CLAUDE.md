# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

PMR Backend (Sistema Integral de Gestión de Información) is a Laravel 12 API-based backend system focused on user authentication, role management, and access control. This is a Spanish-language project with English code conventions, providing a secure foundation for applications that require robust user management with role-based permissions.

## Technology Stack

- **Backend Framework**: Laravel 12
- **PHP Version**: PHP 8.2+
- **Database**: MySQL (configurable via .env)
- **Testing**: Pest PHP
- **Asset Building**: Vite + Tailwind CSS v4
- **Authentication**: Laravel Sanctum
- **Authorization**: Spatie Laravel Permission
- **Queue System**: Database queues
- **Cache**: Database cache

## Development Commands

### Environment Setup

```bash
# Complete project setup (installs dependencies, generates key, runs migrations, builds assets)
composer run setup

# Start development server with queue, logs, and asset watching
composer run dev

# Install dependencies
composer install
npm install

# Environment configuration
cp .env.example .env
php artisan key:generate
php artisan migrate
```

### Testing

```bash
# Run all tests
composer run test
# Or directly:
php artisan test

# Run specific test file
php artisan test tests/Feature/ExampleTest.php
```

### Database Operations

```bash
# Run migrations
php artisan migrate

# Fresh migration with seeding
php artisan migrate:fresh --seed

# Create new migration
php artisan make:migration create_table_name

# Rollback migration
php artisan migrate:rollback
```

### Asset Building

```bash
# Build assets for production
npm run build

# Watch assets during development
npm run dev
```

### Code Quality

```bash
# Code formatting (Laravel Pint)
./vendor/bin/pint

# Check code style
./vendor/bin/pint --test
```

## Core Architecture

### Authentication & Authorization System

PMR implements a robust authentication and authorization system with:

- **Laravel Sanctum**: Token-based API authentication
- **Spatie Laravel Permission**: Role-based access control (RBAC)
- **Email Verification**: Secure user registration with email confirmation
- **Role Management**: Hierarchical permissions system
- **API Security**: Token expiration, refresh mechanisms, and secure logout

### Key Controllers

#### Authentication Controllers

- **LoginController** (`app/Http/Controllers/LoginController.php`):
  - User login and logout
  - Token refresh functionality
  - User profile retrieval
  - Authentication status checking

- **RegisterController** (`app/Http/Controllers/RegisterController.php`):
  - User registration with validation
  - Role assignment for new users
  - Account creation with secure password hashing

- **EmailVerificationController** (`app/Http/Controllers/EmailVerificationController.php`):
  - Email verification processing
  - Verification code resend
  - Verification status checking

#### User Management Controllers

- **UsuarioController** (`app/Http/Controllers/UsuarioController.php`):
  - User profile management
  - CRUD operations for user accounts
  - Profile updates and password changes

- **RoleManagementController** (`app/Http/Controllers/RoleManagementController.php`):
  - Role assignment and management
  - Permission configuration
  - User role tracking

### Services

#### Verifiers Service

The `Verifiers` service (`app/Services/Verifiers.php`) provides validation utilities:

- **Email Validation**: Comprehensive email format and domain validation
- **Data Type Validation**: String, integer, float validation
- **Text Processing**: Normalization and cleaning utilities
- **Search Utilities**: General search and text extraction

### API-First Design

- RESTful API architecture with consistent endpoints
- Standardized JSON responses with proper HTTP status codes
- Comprehensive error handling with detailed messages
- Secure authentication with token-based access
- Role-based endpoint protection

## Project Structure

```markdown
app/
├── Http/
│   └── Controllers/
│       ├── Controller.php                # Base Laravel controller
│       ├── LoginController.php          # Authentication logic
│       ├── RegisterController.php       # User registration
│       ├── EmailVerificationController.php # Email verification
│       ├── UsuarioController.php        # User management
│       └── RoleManagementController.php # Role & permission management
├── Models/
│   ├── User.php                         # User model with roles
│   └── EmailVerification.php            # Email verification model
├── Services/
│   └── Verifiers.php                   # Validation utilities
└── ...

database/
├── migrations/                          # Database migrations
│   ├── 0001_01_01_000000_create_users_table.php
│   ├── 0001_01_01_000002_create_jobs_table.php
│   ├── 2025_10_08_203946_create_personal_access_tokens_table.php
│   ├── 2025_10_14_161014_add_deleted_at_to_users_table.php
│   ├── 2025_10_22_231043_create_permission_tables.php
│   └── 2025_10_24_200000_create_email_verifications_table.php
├── seeders/                            # Database seeders
└── factories/                          # Model factories

routes/
├── api.php                             # API routes (auth & users)
├── web.php                             # Web routes
└── console.php                         # Console routes
```

## Key Features & Patterns

### Authentication Flow

1. **User Registration**:
   - POST `/auth/register` - Create new user account
   - Automatic email verification sending
   - Default role assignment

2. **Email Verification**:
   - POST `/auth/verify-email` - Verify email address
   - POST `/auth/resend-verification` - Resend verification code
   - POST `/auth/check-verification` - Check verification status

3. **Login/Authentication**:
   - POST `/auth/login` - User authentication
   - POST `/auth/logout` - Secure logout
   - GET `/auth/me` - Get current user info
   - POST `/auth/refresh` - Refresh authentication token

### Role-Based Access Control

- **Role Hierarchy**: Admin-sistema > Admin-general > Usuario-interno > Usuario-externo
- **Permission System**: Granular permissions for different actions
- **Dynamic Role Assignment**: Admins can assign roles to users
- **Permission Checking**: Middleware-based route protection

### User Management

- **Profile Management**: Users can update their own profiles
- **Admin Controls**: Administrators can manage all users
- **Secure Operations**: Password hashing and secure data handling
- **Soft Deletes**: User accounts can be soft-deleted

### Validation Pipeline

1. **Input Validation**: Laravel validator rules
2. **Email Verification**: Required for account activation
3. **Business Rules**: Role-based access validation
4. **Security Checks**: Token validation and user permissions

### API Security

- **Token-Based Authentication**: Laravel Sanctum tokens
- **Token Expiration**: Configurable token lifetimes
- **Secure Logout**: Token invalidation on logout
- **CORS Configuration**: Proper cross-origin resource sharing
- **Rate Limiting**: Protection against brute force attacks

## Development Standards

### Code Style

- **PSR Compliance**: Follow PSR-4 autoloading and coding standards
- **English Naming**: All code (variables, functions, classes) in English
- **Spanish Documentation**: Comments and documentation in Spanish
- **PHPDoc Mandatory**: All methods require proper PHPDoc documentation

### Git Workflow

- **Branch Naming**: Use descriptive branch names in English
- **Commit Messages**: Clear, descriptive messages in English
- **Code Review**: All changes should maintain project standards

### Database Conventions

- **Migration Naming**: Descriptive migration names in English
- **Table Names**: Plural snake_case
- **Column Names**: snake_case with descriptive names
- **Foreign Keys**: Standard Laravel foreign key conventions

## Important Notes

### Language Context

- **Code**: All variable names, function names, class names in English
- **Documentation**: Comments, PHPDoc, and external documentation in Spanish
- **User Interface**: Spanish-language responses and error messages
- **Authentication**: Secure user management with role-based access

### Security Features

- Token-based authentication with Laravel Sanctum
- Role-based permissions with Spatie Laravel Permission
- Email verification for account activation
- Secure password hashing and validation
- Rate limiting and brute force protection

### Error Handling

- Standardized JSON error responses
- Consistent HTTP status codes
- Detailed error messages in Spanish
- Authentication and authorization error formatting

### Performance Considerations

- Efficient database queries with proper indexing
- Token caching and optimization
- Secure session management
- Minimal database connections for authentication

## Configuration

### Environment Variables

Key environment variables in `.env`:

- `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `APP_URL` for API base URL
- `QUEUE_CONNECTION` for queue system (email verification)
- `CACHE_STORE` for caching configuration
- `MAIL_*` settings for email verification
- `SUPER_ADMIN_*` settings for initial admin setup

### Default Settings

- Database name: `pmr`
- Authentication: Laravel Sanctum tokens
- Token expiration: 24 hours (configurable)
- Email verification: Required for new accounts
- Default user role: Usuario-externo
- Soft deletes: Enabled for user accounts

This architecture provides a secure and scalable foundation for applications requiring robust user authentication, role-based access control, and comprehensive user management capabilities following Laravel best practices.
