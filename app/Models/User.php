<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be validated.
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:6',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'user_type' => 'string',
        ];
    }

    /**
     * Obtiene la empresa asociada a este usuario como solicitante.
     */
    public function empresa()
    {
        return $this->hasOne(Empresa::class, 'solicitante_id');
    }

    /**
     * Obtiene los tokens de verificación de correo electrónico del usuario.
     */
    public function emailVerifications()
    {
        return $this->hasMany(EmailVerification::class);
    }

    /**
     * Obtiene el token de verificación de correo electrónico activo del usuario.
     */
    public function activeEmailVerification()
    {
        return $this->hasOne(EmailVerification::class)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now());
    }

    /**
     * Verifica si el usuario tiene el correo electrónico verificado.
     */
    public function isEmailVerified(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Verifica si el usuario tiene un token de verificación pendiente y no expirado.
     */
    public function hasPendingEmailVerification(): bool
    {
        return $this->activeEmailVerification()->exists();
    }

    /**
     * Obtiene el rol principal del usuario (para propósitos de visualización).
     */
    public function getPrimaryRoleAttribute(): ?string
    {
        return $this->roles->first()?->name;
    }

    /**
     * Verifica si el usuario es de tipo EXTERNO.
     */
    public function isExternalUser(): bool
    {
        return $this->user_type === 'EXTERNO';
    }

    /**
     * Verifica si el usuario es de tipo INTERNO.
     */
    public function isInternalUser(): bool
    {
        return $this->user_type === 'INTERNO';
    }

    /**
     * Obtiene los datos del usuario en formato para respuesta JSON.
     */
    public function toArrayForApi(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'rfc' => $this->rfc,
            'user_type' => $this->user_type,
            'email_verified_at' => $this->email_verified_at,
            'is_email_verified' => $this->isEmailVerified(),
            'has_pending_verification' => $this->hasPendingEmailVerification(),
            'primary_role' => $this->primary_role,
            'roles' => $this->getRoleNames(),
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
