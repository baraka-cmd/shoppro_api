<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute la colonne `seller_id` à la table `reviews`.
     *
     * Rôle :
     *   - Filtrer les avis par vendeur (modération vendeur)
     *   - Un vendeur ne voit QUE les avis sur SES produits
     *   - NULL = avis sur un produit admin (marketplace)
     *
     * Dénormalisation :
     *   `seller_id` est dupliqué depuis `products.seller_id` pour :
     *   - Éviter une jointure à chaque requête
     *   - Accélérer le dashboard vendeur
     *   - Permettre des index efficaces
     *
     *   La synchronisation est assurée par un Observer sur `Review`
     *   (à créer en Phase 3) qui copie `product.seller_id` à la création.
     *
     * Rétro-compatibilité :
     *   - Tous les avis existants gardent `seller_id = NULL`
     *   - Aucune donnée n'est supprimée ou modifiée
     *   - Le comportement admin/client actuel reste identique
     *
     * Relations :
     *   - Many-to-One avec `users` (un vendeur = plusieurs avis reçus)
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // ============================================
            // 1. COLONNE `seller_id`
            // ============================================
            $table->foreignId('seller_id')
                  ->nullable()
                  ->after('product_id')
                  ->comment('Vendeur du produit évalué (NULL = produit admin)');

            // ============================================
            // 2. CLÉ ÉTRANGÈRE
            // ============================================
            // nullOnDelete : si le vendeur est supprimé, ses avis
            // restent en base et repassent sous gestion admin
            $table->foreign('seller_id', 'fk_reviews_seller')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();

            // ============================================
            // 3. INDEX
            // ============================================
            // Pour les requêtes filtrées par vendeur
            //   - SELECT * FROM reviews WHERE seller_id = X
            $table->index('seller_id', 'idx_reviews_seller');

            // Index composite : avis en attente d'un vendeur
            //   - SELECT * FROM reviews
            //     WHERE seller_id = X AND status = 'pending'
            $table->index(
                ['seller_id', 'status'],
                'idx_reviews_seller_status'
            );

            // Index composite : avis approuvés d'un vendeur
            //   - SELECT AVG(rating) FROM reviews
            //     WHERE seller_id = X AND status = 'approved'
            $table->index(
                ['seller_id', 'status', 'rating'],
                'idx_reviews_seller_status_rating'
            );
        });
    }

    /**
     * Supprime la colonne `seller_id` et ses index.
     *
     * ⚠️ Action destructive :
     *   - Toutes les associations avis → vendeur sont perdues
     *   - Les avis redeviennent "avis admin"
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Supprimer d'abord les index (ordre inverse de création)
            $table->dropIndex('idx_reviews_seller_status_rating');
            $table->dropIndex('idx_reviews_seller_status');
            $table->dropIndex('idx_reviews_seller');

            // Puis la FK
            $table->dropForeign('fk_reviews_seller');

            // Puis la colonne
            $table->dropColumn('seller_id');
        });
    }
};