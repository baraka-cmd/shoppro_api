<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute la colonne `is_pickup_address` à la table `user_addresses`.
     *
     * Rôle :
     *   - Identifier les adresses utilisables comme point de retrait
     *   - Un vendeur peut marquer SON adresse comme "point relais"
     *   - Un client pourra choisir "retrait en boutique" au checkout
     *
     * Cas d'usage :
     *   - Client A a une adresse normale → is_pickup_address = false
     *   - Vendeur B marque son adresse → is_pickup_address = true
     *   - Client choisit "retrait en boutique" → voit les adresses pickup
     *
     * Rétro-compatibilité :
     *   - Toutes les adresses existantes gardent `is_pickup_address = false`
     *   - Le comportement client actuel reste identique
     *   - Aucune modification des écrans checkout existants
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            // ============================================
            // 1. COLONNE `is_pickup_address`
            // ============================================
            $table->boolean('is_pickup_address')
                  ->default(false)
                  ->after('is_active')
                  ->comment('Adresse utilisable comme point de retrait (vendeur)');

            // ============================================
            // 2. COLONNE `pickup_instructions`
            // ============================================
            // Optionnel : instructions spécifiques pour le retrait
            $table->text('pickup_instructions')
                  ->nullable()
                  ->after('is_pickup_address')
                  ->comment('Instructions de retrait (horaires, étage, etc.)');

            // ============================================
            // 3. INDEX
            // ============================================
            // Pour filtrer les points de retrait disponibles
            //   - SELECT * FROM user_addresses
            //     WHERE is_pickup_address = true AND is_active = true
            $table->index(
                ['is_pickup_address', 'is_active'],
                'idx_user_addresses_pickup_active'
            );
        });
    }

    /**
     * Supprime les colonnes pickup.
     *
     * ⚠️ Action destructive :
     *   - Toutes les adresses "point relais" redeviennent normales
     *   - Les instructions de retrait sont perdues
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            // Supprimer d'abord l'index
            $table->dropIndex('idx_user_addresses_pickup_active');

            // Puis les colonnes (ordre inverse)
            $table->dropColumn([
                'pickup_instructions',
                'is_pickup_address',
            ]);
        });
    }
};