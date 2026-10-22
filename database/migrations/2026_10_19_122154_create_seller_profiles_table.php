<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée la table `seller_profiles`.
     *
     * Rôle :
     *   - Étendre la table `users` avec les infos vendeur
     *   - 1-to-1 strict avec `users` (un vendeur = 1 profil)
     *   - Gérer le KYC (vérification d'identité)
     *   - Gérer la finance (commission, balance, payouts)
     *   - Stocker les stats dénormalisées pour perf dashboard
     *
     * ⚠️ Choix d'architecture :
     *   - Ne PAS stocker ces infos dans `user_profiles` (qui est orienté CLIENT)
     *   - Table dédiée = séparation claire des responsabilités
     *   - Peut grandir sans impacter les clients
     *
     * Création automatique :
     *   Un Observer sur `User` créera un `SellerProfile` vide
     *   dès qu'un utilisateur reçoit le rôle `seller`.
     *
     * Soft delete :
     *   Le profil ne se supprime JAMAIS (traçabilité financière).
     *   On utilise `is_active = false` pour désactiver un vendeur.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('seller_profiles', function (Blueprint $table) {
            // ============================================
            // 1. CLÉ PRIMAIRE
            // ============================================
            $table->id();

            // ============================================
            // 2. RELATION USER (1-to-1 strict)
            // ============================================
            $table->foreignId('user_id')
                  ->unique()
                  ->constrained('users')
                  ->cascadeOnDelete()
                  ->comment('Utilisateur vendeur (relation 1-to-1)');

            // ============================================
            // 3. IDENTITÉ BOUTIQUE
            // ============================================
            $table->string('shop_name', 150)
                  ->comment('Nom public de la boutique');

            $table->string('shop_slug', 180)
                  ->unique()
                  ->comment('Slug URL unique (ex: ma-super-boutique)');

            $table->text('description')
                  ->nullable()
                  ->comment('Description publique de la boutique');

            $table->string('logo', 255)
                  ->nullable()
                  ->comment('Chemin du logo boutique');

            $table->string('banner', 255)
                  ->nullable()
                  ->comment('Chemin de la bannière boutique');

            // ============================================
            // 4. CONTACT & LOCALISATION
            // ============================================
            $table->string('phone', 20)
                  ->nullable()
                  ->comment('Téléphone professionnel');

            $table->string('email', 150)
                  ->nullable()
                  ->comment('Email professionnel (contact boutique)');

            $table->string('address', 255)
                  ->nullable()
                  ->comment('Adresse physique de la boutique');

            $table->string('city', 100)
                  ->nullable()
                  ->comment('Ville');

            $table->string('country', 100)
                  ->default('Cameroun')
                  ->comment('Pays');

            // ============================================
            // 5. KYC (Know Your Customer)
            // ============================================
            $table->enum('kyc_status', [
                'pending',   // En attente de vérification
                'verified',  // Vérifié et validé
                'rejected',  // Refusé (documents invalides)
            ])
            ->default('pending')
            ->comment('Statut KYC (vérification identité)');

            $table->timestamp('kyc_verified_at')
                  ->nullable()
                  ->comment('Date de validation KYC');

            $table->text('kyc_rejection_reason')
                  ->nullable()
                  ->comment('Raison du refus KYC (si rejected)');

            // ============================================
            // 6. FINANCE
            // ============================================
            $table->decimal('commission_rate', 5, 2)
                  ->default(10.00)
                  ->comment('Taux de commission ShopPro (%) — défaut 10%');

            // Balance disponible (retirable immédiatement)
            $table->decimal('balance', 15, 2)
                  ->default(0)
                  ->comment('Solde disponible (retirable)');

            // Balance en attente (commandes non livrées encore)
            $table->decimal('pending_balance', 15, 2)
                  ->default(0)
                  ->comment('Solde en attente (commandes non livrées)');

            // ============================================
            // 7. MÉTHODE DE PAIEMENT (PAYOUT)
            // ============================================
            $table->string('payout_method', 50)
                  ->nullable()
                  ->comment('Méthode de retrait : mobile_money, bank, paypal');

            $table->string('payout_account', 100)
                  ->nullable()
                  ->comment('Compte de retrait (numéro mobile money, IBAN, email PayPal)');

            // ============================================
            // 8. STATISTIQUES DÉNORMALISÉES (perf dashboard)
            // ============================================
            $table->integer('products_count')
                  ->default(0)
                  ->comment('Nombre de produits (maintenu par Observer)');

            $table->integer('orders_count')
                  ->default(0)
                  ->comment('Nombre de commandes reçues');

            $table->integer('sales_count')
                  ->default(0)
                  ->comment('Nombre total d\'items vendus');

            $table->decimal('total_revenue', 15, 2)
                  ->default(0)
                  ->comment('Chiffre d\'affaires total (hors commission)');

            $table->decimal('average_rating', 3, 2)
                  ->default(0)
                  ->comment('Note moyenne boutique (0-5)');

            $table->integer('reviews_count')
                  ->default(0)
                  ->comment('Nombre total d\'avis reçus');

            // ============================================
            // 9. STATUT
            // ============================================
            $table->boolean('is_active')
                  ->default(true)
                  ->comment('Vendeur actif (peut vendre)');

            $table->boolean('is_featured')
                  ->default(false)
                  ->comment('Vendeur mis en avant (page d\'accueil)');

            $table->boolean('is_verified')
                  ->default(false)
                  ->comment('Vendeur certifié ShopPro (badge)');

            // ============================================
            // 10. HORODATAGE
            // ============================================
            $table->timestamps();
            $table->softDeletes();

            // ============================================
            // 11. INDEX
            // ============================================
            // Recherche par slug (URL boutique publique)
            $table->index('shop_slug', 'idx_seller_profiles_slug');

            // Filtrer par statut KYC (admin)
            $table->index('kyc_status', 'idx_seller_profiles_kyc');

            // Filtrer par actifs / featured (page d'accueil)
            $table->index(['is_active', 'is_featured'], 'idx_seller_profiles_active_featured');

            // Filtrer par pays / ville (annuaire)
            $table->index(['country', 'city'], 'idx_seller_profiles_location');
        });
    }

    /**
     * Supprime la table `seller_profiles`.
     *
     * ⚠️ Action TRÈS destructive :
     *   - Toutes les données vendeur (KYC, finances, stats) sont perdues
     *   - Les `users` avec rôle `seller` restent, mais sans profil
     *   - Les produits/commandes/avis gardent `seller_id` (intact)
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_profiles');
    }
};