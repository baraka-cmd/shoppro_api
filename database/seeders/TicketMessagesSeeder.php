<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Support\SupportTicket;
use App\Models\Support\TicketMessage;

class TicketMessagesSeeder extends Seeder
{
    /**
     * Seed des messages de tickets
     */
    public function run(): void
    {
        $tickets = SupportTicket::all();

        $userMessages = [
            'Bonjour, j\'ai besoin d\'aide concernant ma commande.',
            'Merci pour votre réponse rapide.',
            'Pouvez-vous me donner plus de détails ?',
            'J\'ai bien reçu l\'information, merci.',
            'Le problème persiste malheureusement.',
        ];

        $adminMessages = [
            'Bonjour, nous avons bien reçu votre demande.',
            'Pouvez-vous nous envoyer votre numéro de commande ?',
            'Nous allons vérifier cela immédiatement.',
            'Votre problème a été résolu.',
            'N\'hésitez pas si vous avez d\'autres questions.',
        ];

        foreach ($tickets as $ticketIndex => $ticket) {
            // 2-5 messages par ticket
            $messageCount = rand(2, 5);
            
            for ($i = 0; $i < $messageCount; $i++) {
                $isAdmin = $i % 2 === 1; // Alterner user/admin
                $sender = $isAdmin ? $ticket->assigned_to : $ticket->user_id;
                $message = $isAdmin 
                    ? $adminMessages[($ticketIndex + $i) % count($adminMessages)]
                    : $userMessages[($ticketIndex + $i) % count($userMessages)];
                
                TicketMessage::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $sender,
                    'message' => $message,
                    'sender_type' => $isAdmin ? 'admin' : 'user',
                    'is_read' => true,
                    'read_at' => now()->subHours(rand(1, 24)),
                ]);
            }
        }
    }
}