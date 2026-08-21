<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Support\Notification;

class NotificationsSeeder extends Seeder
{
    /**
     * Seed des notifications
     */
    public function run(): void
    {
        $users = User::whereHas('roles', function ($q) {
            $q->where('name', 'client');
        })->get();

        $notifications = [
            [
                'title' => 'Bienvenue sur ShopPro !',
                'body' => 'Merci de vous être inscrit. Découvrez nos produits et profitez des promotions.',
                'type' => 'system',
                'icon' => 'welcome',
            ],
            [
                'title' => 'Promotion spéciale',
                'body' => 'Profitez de -20% sur tous les smartphones avec le code PROMO20.',
                'type' => 'promotion',
                'icon' => 'promo',
            ],
            [
                'title' => 'Votre commande est en route',
                'body' => 'Votre commande a été expédiée et arrivera bientôt.',
                'type' => 'order',
                'icon' => 'shipping',
            ],
            [
                'title' => 'Nouveaux produits disponibles',
                'body' => 'Découvrez les derniers produits ajoutés à notre catalogue.',
                'type' => 'system',
                'icon' => 'new',
            ],
            [
                'title' => 'Alerte prix',
                'body' => 'Un produit de votre liste de souhaits a baissé de prix !',
                'type' => 'price_alert',
                'icon' => 'price',
            ],
        ];

        foreach ($users as $userIndex => $user) {
            // Chaque utilisateur reçoit 2-4 notifications
            $notificationCount = rand(2, 4);
            
            for ($i = 0; $i < $notificationCount; $i++) {
                $notificationIndex = ($userIndex + $i) % count($notifications);
                $notification = $notifications[$notificationIndex];
                
                Notification::create([
                    'user_id' => $user->id,
                    'title' => $notification['title'],
                    'body' => $notification['body'],
                    'type' => $notification['type'],
                    'icon' => $notification['icon'],
                    'is_read' => $i > 0,
                    'read_at' => $i > 0 ? now()->subDays(rand(1, 5)) : null,
                    'sent_push' => true,
                    'sent_email' => false,
                    'sent_sms' => false,
                    'sent_at' => now()->subDays(rand(1, 10)),
                    'received_at' => now()->subDays(rand(1, 10)),
                    'is_active' => true,
                ]);
            }
        }
    }
}