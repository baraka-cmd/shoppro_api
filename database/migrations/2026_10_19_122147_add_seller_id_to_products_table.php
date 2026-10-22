<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute la colonne `seller_id` à la table `products`.
     *
     * Rôle :
     *   - Identifier le vendeur propriétaire d'un produit
     *   - NULL = produit géré par l'admin (marketplace ShopPro officielle)
     *   - NOT NULL = produit géré par un vendeur spécifique
     *
     * Rétro-compatibilité :
     *   - Tous les produits existants gardent `seller_id = NULL`
     *   - Aucune donnée n'est supprimée ou modifiée
     *   - Le comportement client/admin actuel reste identique
     *
     * Relations :
     *   - Many-to-One avec `users` (un vendeur = plusieurs produits)
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // ============================================
            // 1. COLONNE `seller_id`
            // ============================================
            $table->foreignId('seller_id')
                  ->nullable()
                  ->after('brand_id')
                  ->comment('Vendeur propriétaire du produit (NULL = produit admin)');

            // ============================================
            // 2. CLÉ ÉTRANGÈRE
            // ============================================
            // nullOnDelete : si le vendeur est supprimé, ses produits
            // restent en base et repassent sous gestion admin
            // (aucune perte de données)
            $table->foreign('seller_id', 'fk_products_seller')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();

            // ============================================
            // 3. INDEX
            // ============================================
            // Pour les requêtes filtrées par vendeur :
            //   - SELECT * FROM products WHERE seller_id = X
            //   - Dashboard vendeur
            //   - Statistiques vendeur
            $table->index('seller_id', 'idx_products_seller');

            // Index composite : produits d'un vendeur actif
            //   - SELECT * FROM products WHERE seller_id = X AND is_active = 1
            $table->index(['seller_id', 'is_active'], 'idx_products_seller_active');
        });
    }

    /**
     * Supprime la colonne `seller_id` et ses index.
     *
     * ⚠️ Cette action est destructive :
     *   - Toutes les associations produit → vendeur sont perdues
     *   - Les produits redeviennent "produits admin" automatiquement
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Supprimer d'abord les index, puis la FK, puis la colonne
            $table->dropIndex('idx_products_seller_active');
            $table->dropIndex('idx_products_seller');
            $table->dropForeign('fk_products_seller');
            $table->dropColumn('seller_id');
        });
    }
};