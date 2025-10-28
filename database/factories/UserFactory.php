<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake('es_MX')->name(),
            'email' => fake('es_MX')->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'rfc' => $this->generateRfc(),
            'user_type' => fake()->randomElement(['INTERNO', 'EXTERNO']),
        ];
    }

    /**
     * Generate a valid Mexican RFC.
     */
    private function generateRfc(): string
    {
        // RFC format: 4 letters + 6 digits (YYMMDD) + 3 characters
        $letters = strtoupper(Str::random(4));
        $date = fake()->dateTimeBetween('-70 years', '-18 years')->format('ymd');
        $suffix = strtoupper(Str::random(2)) . '0';

        return $letters . $date . $suffix;
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create a test user with predictable credentials.
     * Email: test@example.com
     * Password: password123
     */
    public function testUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Usuario de Prueba',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'rfc' => 'PERJ800101HAA',
            'user_type' => 'EXTERNO',
        ]);
    }

    /**
     * Create a super admin user with predictable credentials.
     * Email: admin@pmr.com
     * Password: admin123456
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Super Admin PMR',
            'email' => 'admin@pmr.com',
            'password' => Hash::make('admin123456'),
            'email_verified_at' => now(),
            'rfc' => 'PMR850101000',
            'user_type' => 'INTERNO',
        ]);
    }

    /**
     * Create a Mexican user with Spanish name and .mx domain.
     */
    public function mexican(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake('es_MX')->name(),
            'email' => fake('es_MX')->unique()->safeEmail() . '.mx',
            'email_verified_at' => now(),
            'rfc' => $this->generateRfc(),
            'user_type' => 'EXTERNO',
        ]);
    }

    /**
     * Create multiple test users with sequential emails.
     */
    public function testUsers(int $count = 1): static
    {
        $users = [];
        for ($i = 1; $i <= $count; $i++) {
            $users[] = [
                'name' => "Usuario de Prueba {$i}",
                'email' => "test{$i}@example.com",
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
                'rfc' => 'PERJ' . str_pad((string)($i + 80), 6, '0', STR_PAD_LEFT) . 'HAA',
                'user_type' => 'EXTERNO',
            ];
        }

        return $this->sequence(...$users);
    }
}
