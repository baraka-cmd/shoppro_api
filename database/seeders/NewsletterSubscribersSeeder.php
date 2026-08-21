<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin\NewsletterSubscriber;

class NewsletterSubscribersSeeder extends Seeder
{
    /**
     * Seed des abonnés newsletter
     */
    public function run(): void
    {
        $subscribers = [
            ['email' => 'jean@example.com', 'name' => 'Jean Kamga', 'source' => 'inscription'],
            ['email' => 'marie@example.com', 'name' => 'Marie Ngo', 'source' => 'inscription'],
            ['email' => 'paul@example.com', 'name' => 'Paul Essomba', 'source' => 'checkout'],
            ['email' => 'sophie@example.com', 'name' => 'Sophie Mbarga', 'source' => 'popup'],
            ['email' => 'david@example.com', 'name' => 'David Njoya', 'source' => 'inscription'],
            ['email' => 'claire@example.com', 'name' => 'Claire Tchoua', 'source' => 'checkout'],
            ['email' => 'eric@example.com', 'name' => 'Eric Fotso', 'source' => 'popup'],
            ['email' => 'aline@example.com', 'name' => 'Aline Bello', 'source' => 'inscription'],
            ['email' => 'visiteur1@example.com', 'name' => null, 'source' => 'popup'],
            ['email' => 'visiteur2@example.com', 'name' => null, 'source' => 'checkout'],
        ];

        foreach ($subscribers as $subscriber) {
            NewsletterSubscriber::updateOrCreate(
                ['email' => $subscriber['email']],
                [
                    'email' => $subscriber['email'],
                    'name' => $subscriber['name'],
                    'status' => 'subscribed',
                    'unsubscribe_token' => NewsletterSubscriber::generateUnsubscribeToken(),
                    'subscribed_at' => now()->subDays(rand(1, 60)),
                    'source' => $subscriber['source'],
                ]
            );
        }
    }
}