<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware : EnsureOwnership
 *
 * Vérifie qu'une ressource (route model binding ou paramètre) appartient
 * à l'utilisateur connecté.
 *
 * Générique et réutilisable pour tout type de ressource :
 *   - product, order, review, stock, promotion, payout
 *   - address, notification...
 *
 * Utilisation dans les routes :
 *   // Vérifier que le {product} de la route appartient à l'utilisateur
 *   Route::middleware('ownership:product')->get('/products/{product}', ...);
 *
 *   // Vérifier que le {order} appartient à l'utilisateur
 *   Route::middleware('ownership:order')->get('/orders/{order}', ...);
 *
 *   // Vérifier que le {review} appartient à l'utilisateur
 *   Route::middleware('ownership:review')->get('/reviews/{review}', ...);
 *
 * Configuration par défaut :
 *   - Admin : passe TOUJOURS (super-pouvoir)
 *   - Client : vérifie `user_id`
 *   - Vendeur : vérifie `seller_id`
 *
 * ⚠️ Règles de sécurité :
 *   - Si la ressource n'existe pas → 404
 *   - Si l'utilisateur n'a pas le droit → 403
 *   - Si la ressource n'a PAS de champ d'appartenance → erreur 500 (dev)
 *
 * Cas particuliers :
 *   - Order : utilise `user_id` (client) OU `items.seller_id` (vendeur)
 *   - Le middleware choisit le bon champ selon le rôle
 *
 * ⚠️ Limites :
 *   - Ce middleware est un FILET DE SÉCURITÉ
 *   - Il ne remplace PAS les Policies (défense en profondeur)
 *   - Les vérifications fines (statut, KYC, etc.) sont dans les Policies
 */
class EnsureOwnership
{
    /**
     * Configuration des champs d'appartenance par ressource.
     *
     * Permet de définir :
     *   - Le champ owner côté client (user_id)
     *   - Le champ owner côté vendeur (seller_id)
     *   - Les relations à vérifier si pas de seller_id direct
     *
     * @var array<string, array{client?: string, seller?: string, via?: string, via_field?: string}>
     */
    private array $ownershipMap = [
        'product'      => ['client' => null,       'seller' => 'seller_id'],
        'order'        => ['client' => 'user_id',  'seller' => null,      'via' => 'items',      'via_field' => 'seller_id'],
        'order-item'   => ['client' => null,       'seller' => 'seller_id'],
        'review'       => ['client' => 'user_id',  'seller' => 'seller_id'],
        'stock'        => ['client' => null,       'seller' => 'seller_id'],
        'promotion'    => ['client' => null,       'seller' => 'seller_id'],
        'payout'       => ['client' => null,       'seller' => 'seller_id'],
        'address'      => ['client' => 'user_id',  'seller' => null],
        'notification' => ['client' => 'user_id',  'seller' => null],
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $resource  Le nom de la ressource (product, order, ...)
     * @param  string|null  $param  Le nom du paramètre de route (auto si null)
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $resource, ?string $param = null): Response
    {
        $user = $request->user();

        // ============================================
        // 1. VÉRIFIER L'UTILISATEUR
        // ============================================
        if (!$user) {
            return $this->unauthorized();
        }

        // ============================================
        // 2. ADMIN → TOUJOURS AUTORISÉ
        // ============================================
        if ($user->isAdmin()) {
            return $next($request);
        }

        // ============================================
        // 3. RÉCUPÉRER LA RESSOURCE DE LA ROUTE
        // ============================================
        $param = $param ?: $resource;
        $model = $request->route($param);

        if (!$model) {
            // Pas de ressource dans la route → 404 (ne pas révéler l'URL)
            return $this->notFound();
        }

        // Si la route a reçu un ID au lieu du model (route model binding désactivé),
        // on laisse passer : la Policy du contrôleur gérera.
        if (!($model instanceof Model)) {
            return $next($request);
        }

        // ============================================
        // 4. RÉCUPÉRER LA CONFIG D'APPARTENANCE
        // ============================================
        $config = $this->ownershipMap[$resource] ?? null;

        if (!$config) {
            // Configuration manquante → fail-safe : 403
            // (mieux vaut bloquer que laisser fuiter des données)
            return $this->forbidden('Ressource non configurée pour la vérification d\'appartenance.');
        }

        // ============================================
        // 5. VÉRIFICATION SELON LE RÔLE
        // ============================================
        $isOwner = false;

        if ($user->isSeller()) {
            $isOwner = $this->checkSellerOwnership($user, $model, $config);
        } elseif ($user->isClient()) {
            $isOwner = $this->checkClientOwnership($user, $model, $config);
        }

        // ============================================
        // 6. RÉSULTAT
        // ============================================
        if (!$isOwner) {
            return $this->forbidden(
                'Vous n\'êtes pas autorisé à accéder à cette ressource.'
            );
        }

        return $next($request);
    }

    // ============================================
    // VÉRIFICATION D'APPARTENANCE
    // ============================================

    /**
     * Vérifie si le vendeur possède la ressource.
     */
    private function checkSellerOwnership($user, Model $model, array $config): bool
    {
        // 1. Champ direct `seller_id`
        if (!empty($config['seller']) && isset($model->{$config['seller']})) {
            return $model->{$config['seller']} === $user->id;
        }

        // 2. Vérification via relation (ex: order → items.seller_id)
        if (!empty($config['via']) && !empty($config['via_field'])) {
            $relation = $config['via'];
            $field    = $config['via_field'];

            if (method_exists($model, $relation)) {
                $related = $model->{$relation}();

                // Pour une HasMany : exists()
                if (method_exists($related, 'exists')) {
                    return $related->where($field, $user->id)->exists();
                }
            }
        }

        return false;
    }

    /**
     * Vérifie si le client possède la ressource.
     */
    private function checkClientOwnership($user, Model $model, array $config): bool
    {
        if (!empty($config['client']) && isset($model->{$config['client']})) {
            return $model->{$config['client']} === $user->id;
        }

        // Si pas de champ `client` configuré, et qu'on est client : refus
        return false;
    }

    // ============================================
    // HELPERS DE RÉPONSE
    // ============================================

    private function unauthorized(): Response
    {
        return response()->json([
            'success' => false,
            'message' => 'Non authentifié.',
            'code'    => 'UNAUTHENTICATED',
        ], Response::HTTP_UNAUTHORIZED);
    }

    private function forbidden(string $message): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'code'    => 'FORBIDDEN',
        ], Response::HTTP_FORBIDDEN);
    }

    private function notFound(): Response
    {
        return response()->json([
            'success' => false,
            'message' => 'Ressource introuvable.',
            'code'    => 'NOT_FOUND',
        ], Response::HTTP_NOT_FOUND);
    }
}