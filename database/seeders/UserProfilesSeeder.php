<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\User\UserProfile;

class UserProfilesSeeder extends Seeder
{
    /**
     * Seed des profils utilisateurs
     */
    public function run(): void
    {
        $users = User::whereDoesntHave('profile')->get();

        $profiles = [
            [
                'email' => 'admin@shoppro.com',
                'first_name' => 'Admin',
                'last_name' => 'ShopPro',
                'phone' => '+237 6 50 00 00 00',
                'birth_date' => '1990-01-01',
                'gender' => 'male',
                'country' => 'Cameroun',
                'city' => 'Douala',
                'address' => 'Akwa, Douala',
                'language' => 'fr',
                'currency' => 'FCFA',
            ],
            [
                'email' => 'jean@example.com',
                'first_name' => 'Jean',
                'last_name' => 'Kamga',
                'phone' => '+237 6 51 11 11 11',
                'birth_date' => '1992-05-15',
                'gender' => 'male',
                'country' => 'Cameroun',
                'city' => 'Douala',
                'address' => 'Bonamoussadi, Douala',
                'language' => 'fr',
                'currency' => 'FCFA',
            ],
            [
                'email' => 'marie@example.com',
                'first_name' => 'Marie',
                'last_name' => 'Ngo',
                'phone' => '+237 6 52 22 22 22',
                'birth_date' => '1995-08-20',
                'gender' => 'female',
                'country' => 'Cameroun',
                'city' => 'Yaoundé',
                'address' => 'Bastos, Yaoundé',
                'language' => 'fr',
                'currency' => 'FCFA',
            ],
            [
                'email' => 'paul@example.com',
                'first_name' => 'Paul',
                'last_name' => 'Essomba',
                'phone' => '+237 6 53 33 33 33',
                'birth_date' => '1988-03-10',
                'gender' => 'male',
                'country' => 'Cameroun',
                'city' => 'Douala',
                'address' => 'Akwa, Douala',
                'language' => 'fr',
                'currency' => 'FCFA',
            ],
            [
                'email' => 'sophie@example.com',
                'first_name' => 'Sophie',
                'last_name' => 'Mbarga',
                'phone' => '+237 6 54 44 44 44',
                'birth_date' => '1997-11-25',
                'gender' => 'female',
                'country' => 'Cameroun',
                'city' => 'Bafoussam',
                'address' => 'Centre-ville, Bafoussam',
                'language' => 'fr',
                'currency' => 'FCFA',
            ],
        ];

        foreach ($profiles as $profileData) {
            $user = User::where('email', $profileData['email'])->first();
            
            if ($user && !$user->profile) {
                UserProfile::create([
                    'user_id' => $user->id,
                    'first_name' => $profileData['first_name'],
                    'last_name' => $profileData['last_name'],
                    'phone' => $profileData['phone'],
                    'birth_date' => $profileData['birth_date'],
                    'gender' => $profileData['gender'],
                    'country' => $profileData['country'],
                    'city' => $profileData['city'],
                    'address' => $profileData['address'],
                    'language' => $profileData['language'],
                    'currency' => $profileData['currency'],
                    'newsletter_subscribed' => true,
                    'sms_notifications' => true,
                    'push_notifications' => true,
                    'total_orders' => 0,
                    'total_spent' => 0,
                ]);
            }
        }
    }
}