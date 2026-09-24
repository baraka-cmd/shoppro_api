<?php

namespace App\Http\Controllers\Api\Support;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Support\StoreTicketRequest;
use App\Http\Resources\Api\Support\SupportTicketResource;
use App\Models\Support\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupportTicketController extends Controller
{
    /**
     * Afficher les tickets de l'utilisateur connecté.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = SupportTicket::with(['messages', 'order'])
                ->forUser(Auth::id())
                ->latest();

            // Filtre par statut
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filtre par catégorie
            if ($request->has('category')) {
                $query->byCategory($request->category);
            }

            $tickets = $query->paginate($request->per_page ?? 15);

            return response()->json([
                'success' => true,
                'message' => 'Tickets récupérés avec succès',
                'data' => SupportTicketResource::collection($tickets),
                'pagination' => [
                    'total' => $tickets->total(),
                    'per_page' => $tickets->perPage(),
                    'current_page' => $tickets->currentPage(),
                    'last_page' => $tickets->lastPage(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des tickets',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Créer un nouveau ticket.
     *
     * @param StoreTicketRequest $request
     * @return JsonResponse
     */
    public function store(StoreTicketRequest $request): JsonResponse
    {
        try {
            $ticket = SupportTicket::create([
                'ticket_number' => SupportTicket::generateTicketNumber(),
                'user_id' => Auth::id(),
                'order_id' => $request->order_id,
                'subject' => $request->subject,
                'description' => $request->description,
                'category' => $request->category,
                'status' => 'open',
                'priority' => $request->priority ?? 'medium',
            ]);

            // Charger les relations disponibles
            $ticket->load(['messages', 'order']);

            return response()->json([
                'success' => true,
                'message' => 'Ticket créé avec succès',
                'data' => new SupportTicketResource($ticket),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du ticket',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Afficher un ticket spécifique.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $ticket = SupportTicket::with(['messages', 'order', 'user'])
                ->forUser(Auth::id())
                ->findOrFail($id);

            // Marquer les messages admin comme lus
            $ticket->messages()
                ->where('sender_type', 'admin')
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Ticket récupéré avec succès',
                'data' => new SupportTicketResource($ticket),
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
     * Fermer un ticket.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function close(int $id): JsonResponse
    {
        try {
            $ticket = SupportTicket::forUser(Auth::id())
                ->findOrFail($id);

            if ($ticket->isClosed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce ticket est déjà fermé',
                ], 422);
            }

            $ticket->markAsClosed();

            return response()->json([
                'success' => true,
                'message' => 'Ticket fermé avec succès',
                'data' => new SupportTicketResource($ticket),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la fermeture du ticket',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}