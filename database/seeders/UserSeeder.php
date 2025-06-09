<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User; // Importe seu model User
use Illuminate\Support\Facades\Hash; // Para criptografar a senha


class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'username' => 'admin',
            'full_name' => 'Administrador',
            'email' => 'admin@lagoasanta.mg.gov.br',
            'password' => Hash::make('senha123'), // Lembre-se de usar uma senha segura
            // Adicione outros campos necessários para seu model User
            // 'email_verified_at' => now(), // Se você quiser que o email já venha verificado
        ]);
        User::create([
            'username' => 'admin2',
            'full_name' => 'Administrador 2',
            'email' => 'admin2@lagoasanta.mg.gov.br',
            'password' => Hash::make('123senha'), // Lembre-se de usar uma senha segura
            // Adicione outros campos necessários para seu model User
            // 'email_verified_at' => now(), // Se você quiser que o email já venha verificado
        ]);
    }
}
