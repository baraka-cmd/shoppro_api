<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\User\Role;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Seed des utilisateurs
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $sellerRole = Role::where('name', 'seller')->first();
        $clientRole = Role::where('name', 'client')->first();

        $users = [
            [
                'name' => 'Admin ShopPro',
                'email' => 'admin@shoppro.com',
                'password' => Hash::make('password123'),
                'role' => $adminRole,
            ],
            [
                'name' => 'Vendeur ShopPro',
                'email' => 'vendeur@shoppro.com',
                'password' => Hash::make('password123'),
                'role' => $sellerRole,
            ],
            [
                'name' => 'Jean Kamga',
                'email' => 'jean@example.com',
                'password' => Hash::make('password123'),
                'role' => $clientRole,
            ],
            [
                'name' => 'Marie Ngo',
                'email' => 'marie@example.com',
                'password' => Hash::make('password123'),
                'role' => $clientRole,
            ],
            [
                'name' => 'Paul Essomba',
                'email' => 'paul@example.com',
                'password' => Hash::make('password123'),
                'role' => $clientRole,
            ],
            [
                'name' => 'Sophie Mbarga',
                'email' => 'sophie@example.com',
                'password' => Hash::make('password123'),
                'role' => $clientRole,
            ],
            [
                'name' => 'David Njoya',
                'email' => 'david@example.com',
                'password' => Hash::make('password123'),
                'role' => $clientRole,
            ],
            [
                'name' => 'Claire Tchoua',
                'email' => 'claire@example.com',
                'password' => Hash::make('password123'),
                'role' => $clientRole,
            ],
        ];

        foreach ($users as $userData) {
            $role = $userData['role'];
            unset($userData['role']);

            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            if ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }
        }
    }
}