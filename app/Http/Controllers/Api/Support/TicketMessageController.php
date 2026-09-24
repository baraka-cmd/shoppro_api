<?php

namespace App\Http\Controllers\Api\Support;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Support\TicketMessageRequest;
use App\Http\Resources\Api\Support\TicketMessageResource;
use App\Models\Support\SupportTicket;
use App\Models\Support\TicketMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TicketMessageController extends Controller
{
    /**
     * Afficher les messages d'un ticket.
     *
     * @param int $ticketId
     * @return JsonResponse
     */
    public function index(int $ticketId): JsonResponse
    {
        try {
            // Vérifier que le ticket appartient à l'utilisateur
            $ticket = SupportTicket::forUser(Auth::id())->findOrFail($ticketId);

            $messages = $ticket->messages()
                ->with('user')
                ->orderBy('created_at', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Messages récupérés avec succès',
                'data' => TicketMessageResource::collection($messages),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket introuvable',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Envoyer un nouveau message dans un ticket.
     *
     * @param TicketMessageRequest $request
     * @param int $ticketId
     * @return JsonResponse
     */
    public function store(TicketMessageRequest $request, int $ticketId): JsonResponse
    {
        try {
            // Vérifier que le ticket appartient à l'utilisateur
            $ticket = SupportTicket::forUser(Auth::id())->findOrFail($ticketId);

            // Vérifier que le ticket n'est pas fermé
            if ($ticket->isClosed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce ticket est fermé. Vous ne pouvez plus envoyer de messages.',
                ], 422);
            }

            $message = TicketMessage::create([
                'support_ticket_id' => $ticket->id,
                'user_id' => Auth::id(),
                'message' => $request->message,
                'attachment' => $request->attachment,
                'sender_type' => 'user',
                'is_read' => false,
            ]);

            // Mettre à jour le statut du ticket si nécessaire
            if ($ticket->isOpen()) {
                $ticket->markAsWaitingUser();
            }

            return response()->json([
                'success' => true,
                'message' => 'Message envoyé avec succès',
                'data' => new TicketMessageResource($message),
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