<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration : add_onboarding_to_seller_profiles_table
 *
 * Ajoute les colonnes nécessaires pour l'onboarding vendeur.
 *
 * Rôle :
 *   - Tracker les étapes complétées par le vendeur (checklist)
 *   - Savoir quand l'onboarding a été terminé (pour cacher la checklist)
 *   - Permettre au vendeur de masquer la checklist manuellement
 *
 * Colonnes ajoutées :
 *   - onboarding_completed_at        : date de complétion (5/5 étapes)
 *   - onboarding_dismissed_at        : date où le vendeur a masqué la checklist
 *   - onboarding_steps               : JSON des étapes complétées (audit/debug)
 *
 * Rétro-compatibilité :
 *   - Toutes les colonnes sont NULLABLE
 *   - Aucune donnée n'est modifiée
 *   - Le comportement actuel reste identique
 *
 * Backfill :
 *   - On considère que tous les profils existants ont déjà vu l'onboarding
 *     (pour ne pas spammer les vendeurs actuels avec une checklist)
 *   - On marque `onboarding_dismissed_at = now()` pour les profils existants
 *
 * @return void
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table) {
            // ============================================
            // 1. TIMESTAMP DE COMPLÉTION
            // ============================================
            // Rempli automatiquement quand les 5 étapes sont complétées
            // Une fois rempli, la checklist ne s'affiche plus jamais
            $table->timestamp('onboarding_completed_at')
                  ->nullable()
                  ->after('kyc_verified_at')
                  ->comment('Date de complétion des 5 étapes d\'onboarding');

            // ============================================
            // 2. TIMESTAMP DE MASQUAGE MANUEL
            // ============================================
            // Le vendeur peut masquer la checklist sans tout compléter
            // (ex: il ne veut pas publier tout de suite)
            $table->timestamp('onboarding_dismissed_at')
                  ->nullable()
                  ->after('onboarding_completed_at')
                  ->comment('Date où le vendeur a masqué manuellement la checklist');

            // ============================================
            // 3. JSON DES ÉTAPES COMPLÉTÉES
            // ============================================
            // Structure :
            //   {
            //     "profile_completed": true,
            //     "kyc_validated": true,
            //     "first_product_published": false,
            //     "first_order_received": false,
            //     "first_order_shipped": false
            //   }
            //
            // Utile pour :
            //   - Audit (savoir QUAND chaque étape a été complétée)
            //   - Debug (si un vendeur dit "la checklist bug")
            //   - Analytics (taux de complétion, temps moyen par étape)
            $table->json('onboarding_steps')
                  ->nullable()
                  ->after('onboarding_dismissed_at')
                  ->comment('JSON des étapes complétées avec timestamps');

            // ============================================
            // 4. INDEX
            // ============================================
            // Pour les requêtes admin : "combien de vendeurs n'ont pas fini l'onboarding"
            $table->index('onboarding_completed_at', 'idx_seller_onboarding_completed');
        });

        // ============================================
        // 5. BACKFILL — Vendeurs existants
        // ============================================
        // Pour éviter de spammer les vendeurs actuels avec une checklist
        // qu'ils n'ont jamais eue, on les considère comme "dismissed".
        //
        // Nouveaux vendeurs (créés après cette migration) : NULL = checklist visible
        DB::table('seller_profiles')
            ->whereNull('onboarding_dismissed_at')
            ->update([
                'onboarding_dismissed_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table) {
            $table->dropIndex('idx_seller_onboarding_completed');
            $table->dropColumn([
                'onboarding_completed_at',
                'onboarding_dismissed_at',
                'onboarding_steps',
            ]);
        });
    }
};