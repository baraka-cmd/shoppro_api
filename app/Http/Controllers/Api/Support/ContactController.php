<?php

namespace App\Http\Controllers\Api\Support;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Support\ContactRequest;
use App\Models\Support\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ContactController extends Controller
{
    /**
     * Envoyer un message de contact.
     * Si l'utilisateur est connecté, créer un ticket.
     * Sinon, enregistrer comme ticket avec user_id null ou zéro.
     *
     * @param ContactRequest $request
     * @return JsonResponse
     */
    public function store(ContactRequest $request): JsonResponse
    {
        try {
            // Déterminer l'utilisateur (null si invité)
            $userId = Auth::check() ? Auth::id() : null;

            $ticket = SupportTicket::create([
                'ticket_number' => SupportTicket::generateTicketNumber(),
                'user_id' => $userId,
                'order_id' => $request->order_id,
                'subject' => $request->subject,
                'category' => $request->category,
                'status' => 'open',
                'priority' => $request->priority ?? 'medium',
                'message' => $request->message,
                'attachment' => $request->attachment,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Message envoyé avec succès. Notre équipe vous répondra rapidement.',
                'data' => [
                    'ticket_number' => $ticket->ticket_number,
                    'status' => $ticket->status,
                ],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du message',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}