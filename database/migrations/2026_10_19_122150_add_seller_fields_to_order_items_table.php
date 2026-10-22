<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute les colonnes de workflow vendeur à `order_items`.
     *
     * Rôle :
     *   - Identifier le vendeur d'un article
     *   - Permettre au vendeur de suivre SON workflow
     *   - NE PAS toucher à `item_status` existant (statut client global)
     *
     * Double réalité :
     *   - `item_status` (existant) = statut global vu par le client
     *       pending → shipped → delivered / cancelled / returned
     *   - `seller_status` (nouveau) = workflow interne vendeur
     *       to_accept → accepted → preparing → ready_to_ship → shipped
     *
     * Synchronisation :
     *   Quand le vendeur passe `seller_status = 'shipped'`,
     *   le service vendeur mettra aussi `item_status = 'shipped'`.
     *
     * Rétro-compatibilité :
     *   - Tous les items existants gardent `seller_id = NULL`
     *   - Tous les items existants gardent `seller_status = NULL`
     *   - Le comportement client/admin actuel reste identique
     *
     * Relations :
     *   - Many-to-One avec `users` (un vendeur = plusieurs items vendus)
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // ============================================
            // 1. COLONNE `seller_id`
            // ============================================
            $table->foreignId('seller_id')
                  ->nullable()
                  ->after('product_id')
                  ->comment('Vendeur du produit (NULL = produit admin)');

            // ============================================
            // 2. CLÉ ÉTRANGÈRE `seller_id`
            // ============================================
            $table->foreign('seller_id', 'fk_order_items_seller')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();

            // ============================================
            // 3. COLONNE `seller_status`
            // ============================================
            // Workflow vendeur (indépendant de `item_status` client)
            $table->enum('seller_status', [
                'to_accept',      // Vendeur n'a pas encore accepté
                'accepted',       // Vendeur a accepté la commande
                'preparing',      // Vendeur prépare le colis
                'ready_to_ship',  // Colis prêt à être remis au livreur
                'shipped',        // Colis remis au livreur
                'delivered',      // Client a reçu
                'cancelled',      // Annulé par vendeur (rupture, etc.)
                'rejected',       // Refusé par vendeur (KYC, stock, etc.)
            ])
            ->nullable()
            ->after('item_status')
            ->comment('Workflow vendeur (NULL = produit admin, pas de workflow)');

            // ============================================
            // 4. TIMESTAMPS DE WORKFLOW
            // ============================================
            // Date d'acceptation par le vendeur (SLA : 24h)
            $table->timestamp('seller_accepted_at')
                  ->nullable()
                  ->after('seller_status')
                  ->comment('Date d\'acceptation par le vendeur');

            // Date d\'expédition par le vendeur
            $table->timestamp('seller_shipped_at')
                  ->nullable()
                  ->after('seller_accepted_at')
                  ->comment('Date d\'expédition par le vendeur');

            // ============================================
            // 5. INDEX
            // ============================================
            // Pour les requêtes filtrées par vendeur
            $table->index('seller_id', 'idx_order_items_seller');

            // Pour les requêtes filtrées par statut vendeur
            //   - SELECT * FROM order_items WHERE seller_id = X AND seller_status = 'to_accept'
            $table->index('seller_status', 'idx_order_items_seller_status');

            // Index composite pour le dashboard vendeur
            //   - Compter les items à accepter par vendeur
            $table->index(
                ['seller_id', 'seller_status'],
                'idx_order_items_seller_and_status'
            );
        });
    }

    /**
     * Supprime toutes les colonnes de workflow vendeur.
     *
     * ⚠️ Action destructive :
     *   - Toutes les traces du workflow vendeur sont perdues
     *   - Les items redeviennent "items admin" (seller_id = NULL)
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Supprimer d'abord les index
            $table->dropIndex('idx_order_items_seller_and_status');
            $table->dropIndex('idx_order_items_seller_status');
            $table->dropIndex('idx_order_items_seller');

            // Puis la FK
            $table->dropForeign('fk_order_items_seller');

            // Puis les colonnes (dans l'ordre inverse d'ajout)
            $table->dropColumn([
                'seller_shipped_at',
                'seller_accepted_at',
                'seller_status',
                'seller_id',
            ]);
        });
    }
};