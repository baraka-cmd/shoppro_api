<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\User\UserAddress;

class UserAddressesSeeder extends Seeder
{
    /**
     * Seed des adresses utilisateurs
     */
    public function run(): void
    {
        $users = User::whereHas('roles', function ($q) {
            $q->where('name', 'client');
        })->get();

        $addresses = [
            [
                'user_email' => 'jean@example.com',
                'full_name' => 'Jean Kamga',
                'phone' => '+237 6 51 11 11 11',
                'email' => 'jean@example.com',
                'address_line1' => 'Bonamoussadi, Rue 123',
                'address_line2' => 'Près de la pharmacie',
                'city' => 'Douala',
                'state' => 'Littoral',
                'country' => 'Cameroun',
                'type' => 'home',
                'is_default' => true,
                'delivery_instructions' => 'Appeler à l\'arrivée',
            ],
            [
                'user_email' => 'jean@example.com',
                'full_name' => 'Jean Kamga',
                'phone' => '+237 6 51 11 11 11',
                'email' => 'jean@example.com',
                'address_line1' => 'Akwa, Rue du Travail',
                'address_line2' => 'Immeuble ABC, 3e étage',
                'city' => 'Douala',
                'state' => 'Littoral',
                'country' => 'Cameroun',
                'type' => 'work',
                'is_default' => false,
                'delivery_instructions' => 'Livrer avant 17h',
            ],
            [
                'user_email' => 'marie@example.com',
                'full_name' => 'Marie Ngo',
                'phone' => '+237 6 52 22 22 22',
                'email' => 'marie@example.com',
                'address_line1' => 'Bastos, Rue 456',
                'address_line2' => 'Derrière le supermarché',
                'city' => 'Yaoundé',
                'state' => 'Centre',
                'country' => 'Cameroun',
                'type' => 'home',
                'is_default' => true,
                'delivery_instructions' => null,
            ],
            [
                'user_email' => 'paul@example.com',
                'full_name' => 'Paul Essomba',
                'phone' => '+237 6 53 33 33 33',
                'email' => 'paul@example.com',
                'address_line1' => 'Akwa, Rue des Fleurs',
                'address_line2' => null,
                'city' => 'Douala',
                'state' => 'Littoral',
                'country' => 'Cameroun',
                'type' => 'home',
                'is_default' => true,
                'delivery_instructions' => 'Sonner à l\'interphone',
            ],
            [
                'user_email' => 'sophie@example.com',
                'full_name' => 'Sophie Mbarga',
                'phone' => '+237 6 54 44 44 44',
                'email' => 'sophie@example.com',
                'address_line1' => 'Centre-ville, Rue Principale',
                'address_line2' => 'Face à la mairie',
                'city' => 'Bafoussam',
                'state' => 'Ouest',
                'country' => 'Cameroun',
                'type' => 'home',
                'is_default' => true,
                'delivery_instructions' => null,
            ],
        ];

        foreach ($addresses as $addressData) {
            $user = User::where('email', $addressData['user_email'])->first();
            
            if ($user) {
                UserAddress::create([
                    'user_id' => $user->id,
                    'full_name' => $addressData['full_name'],
                    'phone' => $addressData['phone'],
                    'email' => $addressData['email'],
                    'address_line1' => $addressData['address_line1'],
                    'address_line2' => $addressData['address_line2'],
                    'city' => $addressData['city'],
                    'state' => $addressData['state'],
                    'country' => $addressData['country'],
                    'type' => $addressData['type'],
                    'is_default' => $addressData['is_default'],
                    'delivery_instructions' => $addressData['delivery_instructions'],
                    'is_active' => true,
                ]);
            }
        }
    }
}