<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailVerification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
        'verified_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * Get the user that owns the email verification.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the verification is expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the verification is verified.
     *
     * @return bool
     */
    public function isVerified(): bool
    {
        return !is_null($this->verified_at);
    }

    /**
     * Mark the verification as verified.
     *
     * @return bool
     */
    public function markAsVerified(): bool
    {
        return $this->update(['verified_at' => now()]);
    }

    /**
     * Generate a secure random token.
     *
     * @return string
     */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Create a verification token for the user.
     *
     * @param \App\Models\User $user
     * @param int $expiresInMinutes
     * @return static
     */
    public static function createForUser(User $user, int $expiresInMinutes = 60): static
    {
        // Delete any existing unverified tokens for this user
        static::where('user_id', $user->id)
            ->whereNull('verified_at')
            ->delete();

        return static::create([
            'user_id' => $user->id,
            'token' => static::generateToken(),
            'expires_at' => now()->addMinutes($expiresInMinutes),
        ]);
    }

    /**
     * Find a verification by token.
     *
     * @param string $token
     * @return static|null
     */
    public static function findByToken(string $token): ?static
    {
        return static::where('token', $token)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Find any verification by token (including expired ones).
     *
     * @param string $token
     * @return static|null
     */
    public static function findAnyByToken(string $token): ?static
    {
        return static::where('token', $token)
            ->whereNull('verified_at')
            ->first();
    }
}