<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener credenciales desde variables de entorno o usar valores por defecto
        $email = env('SUPER_ADMIN_EMAIL', 'admin@pmr.com');
        $name = env('SUPER_ADMIN_NAME', 'Super Admin PMR');
        $password = env('SUPER_ADMIN_PASSWORD', 'admin123456');
        $rfc = 'PMR850101000';

        // Verificar si el super admin ya existe para no duplicarlo
        $existingAdmin = User::where('email', $email)->first();

        if ($existingAdmin) {
            $this->command->info('⚠️  Super Admin ya existe: ' . $email);
            return;
        }

        // Crear el usuario super admin
        $superAdmin = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'rfc' => $rfc,
            'user_type' => 'INTERNO'
        ]);

        $this->command->info('✅ Super Admin creado exitosamente:');
        $this->command->info('   Email: ' . $email);
        $this->command->info('   Password: ' . $password);
        $this->command->info('   RFC: ' . $rfc);
        $this->command->info('   ID: ' . $superAdmin->id);
    }
}