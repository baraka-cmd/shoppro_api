<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute la colonne `seller_id` à la table `promotions`.
     *
     * Rôle :
     *   - Permettre à un vendeur de créer des promotions
     *     UNIQUEMENT sur ses propres produits
     *   - NULL = promotion globale créée par l'admin
     *   - NOT NULL = promotion locale créée par un vendeur
     *
     * Cas d'usage :
     *   - Admin crée "Black Friday global -50%" → seller_id = NULL
     *   - Vendeur X crée "Promo boutique X -20%" → seller_id = X
     *   - Client voit TOUTES les promos (globales + locales)
     *
     * Rétro-compatibilité :
     *   - Toutes les promotions existantes gardent `seller_id = NULL`
     *   - Le comportement client/admin actuel reste identique
     *
     * Relations :
     *   - Many-to-One avec `users` (un vendeur = plusieurs promotions)
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            // ============================================
            // 1. COLONNE `seller_id`
            // ============================================
            $table->foreignId('seller_id')
                  ->nullable()
                  ->after('id')
                  ->comment('Vendeur créateur de la promotion (NULL = admin/marketplace)');

            // ============================================
            // 2. CLÉ ÉTRANGÈRE
            // ============================================
            // nullOnDelete : si le vendeur est supprimé, ses promos
            // restent en base et repassent sous gestion admin
            $table->foreign('seller_id', 'fk_promotions_seller')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();

            // ============================================
            // 3. INDEX
            // ============================================
            // Pour les requêtes filtrées par vendeur
            //   - SELECT * FROM promotions WHERE seller_id = X
            $table->index('seller_id', 'idx_promotions_seller');

            // Index composite : promos actives d'un vendeur
            //   - SELECT * FROM promotions
            //     WHERE seller_id = X AND is_active = 1
            //     AND start_date <= NOW() AND end_date >= NOW()
            $table->index(
                ['seller_id', 'is_active', 'start_date', 'end_date'],
                'idx_promotions_seller_active'
            );
        });
    }

    /**
     * Supprime la colonne `seller_id` et ses index.
     *
     * ⚠️ Action destructive :
     *   - Toutes les associations promo → vendeur sont perdues
     *   - Les promos redeviennent "promos admin"
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            // Supprimer d'abord les index
            $table->dropIndex('idx_promotions_seller_active');
            $table->dropIndex('idx_promotions_seller');

            // Puis la FK
            $table->dropForeign('fk_promotions_seller');

            // Puis la colonne
            $table->dropColumn('seller_id');
        });
    }
};