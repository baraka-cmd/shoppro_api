<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Support\SupportTicket;

class SupportTicketsSeeder extends Seeder
{
    /**
     * Seed des tickets support
     */
    public function run(): void
    {
        $users = User::whereHas('roles', function ($q) {
            $q->where('name', 'client');
        })->get();

        $adminUser = User::where('email', 'admin@shoppro.com')->first();

        $tickets = [
            [
                'subject' => 'Problème avec ma commande',
                'description' => 'Je n\'ai pas reçu ma commande alors que le statut indique livrée.',
                'category' => 'order',
                'priority' => 'high',
                'status' => 'open',
            ],
            [
                'subject' => 'Question sur le paiement',
                'description' => 'Le paiement a été débité deux fois, comment être remboursé ?',
                'category' => 'payment',
                'priority' => 'urgent',
                'status' => 'pending',
            ],
            [
                'subject' => 'Délai de livraison',
                'description' => 'Quel est le délai de livraison pour Douala ?',
                'category' => 'delivery',
                'priority' => 'low',
                'status' => 'resolved',
            ],
            [
                'subject' => 'Produit défectueux',
                'description' => 'Le smartphone reçu ne s\'allume pas.',
                'category' => 'product',
                'priority' => 'high',
                'status' => 'open',
            ],
            [
                'subject' => 'Modification de mon profil',
                'description' => 'Je n\'arrive pas à modifier mon adresse email.',
                'category' => 'account',
                'priority' => 'medium',
                'status' => 'resolved',
            ],
        ];

        foreach ($tickets as $index => $ticketData) {
            $user = $users[rand(0, count($users) - 1)];
            
            SupportTicket::create([
                'ticket_number' => SupportTicket::generateTicketNumber(),
                'user_id' => $user->id,
                'order_id' => null,
                'subject' => $ticketData['subject'],
                'description' => $ticketData['description'],
                'category' => $ticketData['category'],
                'priority' => $ticketData['priority'],
                'status' => $ticketData['status'],
                'assigned_to' => $adminUser?->id,
                'resolved_at' => $ticketData['status'] === 'resolved' ? now()->subDays(rand(1, 10)) : null,
                'closed_at' => null,
                'last_reply_at' => now()->subHours(rand(1, 72)),
                'satisfaction_rating' => $ticketData['status'] === 'resolved' ? rand(3, 5) : null,
            ]);
        }
    }
}