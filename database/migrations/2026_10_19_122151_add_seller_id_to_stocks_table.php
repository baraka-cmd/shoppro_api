<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute la colonne `seller_id` à la table `stocks`.
     *
     * Rôle :
     *   - Identifier le vendeur propriétaire du stock
     *   - Permettre au vendeur de ne voir QUE ses stocks
     *   - NULL = stock d'un entrepôt admin (warehouse)
     *   - NOT NULL = stock d'un vendeur spécifique
     *
     * ⚠️ Cas métier :
     *   Un vendeur peut avoir :
     *   - Soit son propre stock isolé (seller_id = lui, warehouse = son entrepôt)
     *   - Soit un stock partagé dans l'entrepôt admin (seller_id = lui, warehouse = admin)
     *   Le couple (seller_id, warehouse_id) reste unique.
     *
     * Rétro-compatibilité :
     *   - Tous les stocks existants gardent `seller_id = NULL`
     *   - Aucune donnée n'est supprimée ou modifiée
     *   - Le comportement admin actuel reste identique
     *
     * Relations :
     *   - Many-to-One avec `users` (un vendeur = plusieurs stocks)
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            // ============================================
            // 1. COLONNE `seller_id`
            // ============================================
            $table->foreignId('seller_id')
                  ->nullable()
                  ->after('product_id')
                  ->comment('Vendeur propriétaire du stock (NULL = stock admin)');

            // ============================================
            // 2. CLÉ ÉTRANGÈRE
            // ============================================
            // nullOnDelete : si le vendeur est supprimé, ses stocks
            // restent en base et repassent sous gestion admin
            $table->foreign('seller_id', 'fk_stocks_seller')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();

            // ============================================
            // 3. INDEX
            // ============================================
            // Pour les requêtes filtrées par vendeur
            //   - SELECT * FROM stocks WHERE seller_id = X
            $table->index('seller_id', 'idx_stocks_seller');

            // Index composite pour le dashboard vendeur
            //   - Compter les stocks faibles d'un vendeur
            $table->index(
                ['seller_id', 'is_active'],
                'idx_stocks_seller_active'
            );
        });
    }

    /**
     * Supprime la colonne `seller_id` et ses index.
     *
     * ⚠️ Action destructive :
     *   - Toutes les associations stock → vendeur sont perdues
     *   - Les stocks redeviennent "stocks admin"
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            // Supprimer d'abord les index
            $table->dropIndex('idx_stocks_seller_active');
            $table->dropIndex('idx_stocks_seller');

            // Puis la FK
            $table->dropForeign('fk_stocks_seller');

            // Puis la colonne
            $table->dropColumn('seller_id');
        });
    }
};