<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée la table `seller_transactions`.
     *
     * Rôle :
     *   - Registre comptable IMMUABLE des mouvements financiers vendeur
     *   - Traçabilité complète (audit comptable)
     *   - Historique détaillé pour le dashboard vendeur
     *
     * ⚠️ Choix d'architecture :
     *   - Table JAMAIS soft-deleted (preuve comptable)
     *   - Lignes IMMUABLES après création (sauf statut de payout)
     *   - Chaque transaction = 1 ligne immuable
     *   - Correction = nouvelle ligne de type 'adjustment'
     *
     * Types de transaction :
     *   - 'sale'        : vente d'un produit (crédit)
     *   - 'commission'  : commission ShopPro (débit)
     *   - 'refund'      : remboursement client (débit)
     *   - 'payout'      : retrait vendeur (débit)
     *   - 'adjustment'  : correction manuelle admin (crédit/débit)
     *
     * Relations :
     *   - Many-to-One avec `users` (seller)
     *   - Many-to-One avec `orders` (optionnel — si lié à une commande)
     *   - Many-to-One avec `order_items` (optionnel — si lié à un item)
     *   - Many-to-One avec `seller_payouts` (optionnel — si lié à un payout)
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('seller_transactions', function (Blueprint $table) {
            // ============================================
            // 1. CLÉ PRIMAIRE
            // ============================================
            $table->id();

            // ============================================
            // 2. RÉFÉRENCE UNIQUE (audit)
            // ============================================
            $table->string('reference', 50)
                  ->unique()
                  ->comment('Référence unique de la transaction (TXN-YYYYMMDD-XXXXXX)');

            // ============================================
            // 3. RELATIONS VENDEUR
            // ============================================
            $table->foreignId('seller_id')
                  ->constrained('users')
                  ->cascadeOnDelete()
                  ->comment('Vendeur concerné par la transaction');

            // ============================================
            // 4. RELATIONS MÉTIER (toutes nullables)
            // ============================================
            $table->foreignId('order_id')
                  ->nullable()
                  ->constrained('orders')
                  ->nullOnDelete()
                  ->comment('Commande source (null si payout/adjustment)');

            $table->foreignId('order_item_id')
                  ->nullable()
                  ->constrained('order_items')
                  ->nullOnDelete()
                  ->comment('Item source (null si payout/adjustment)');

            $table->foreignId('payout_id')
                  ->nullable()
                  ->constrained('seller_payouts')
                  ->nullOnDelete()
                  ->comment('Payout lié (null si sale/commission/refund)');

            // ============================================
            // 5. TYPE & STATUT
            // ============================================
            $table->enum('type', [
                'sale',        // Vente d'un produit
                'commission',  // Commission ShopPro
                'refund',      // Remboursement client
                'payout',      // Retrait vendeur
                'adjustment',  // Correction admin
            ])
            ->comment('Type de transaction');

            $table->enum('direction', [
                'credit',  // Crédit (ajout au solde vendeur)
                'debit',   // Débit (retrait du solde vendeur)
            ])
            ->comment('Sens de la transaction (crédit ou débit)');

            // ============================================
            // 6. MONTANTS
            // ============================================
            // Montant brut (avant commission)
            $table->decimal('amount', 15, 2)
                  ->comment('Montant brut de la transaction');

            // Commission (uniquement pour type = 'sale' ou 'commission')
            $table->decimal('commission_amount', 15, 2)
                  ->default(0)
                  ->comment('Commission ShopPro prélevée');

            // Montant net (amount - commission pour sale, ou amount pour les autres)
            $table->decimal('net_amount', 15, 2)
                  ->comment('Montant net crédité/débité au vendeur');

            // Devise
            $table->string('currency', 10)
                  ->default('FCFA')
                  ->comment('Devise de la transaction');

            // ============================================
            // 7. DESCRIPTION & MÉTADONNÉES
            // ============================================
            $table->string('description', 255)
                  ->nullable()
                  ->comment('Description lisible de la transaction');

            $table->json('metadata')
                  ->nullable()
                  ->comment('Données additionnelles (JSON : commission_rate, etc.)');

            // ============================================
            // 8. HORODATAGE
            // ============================================
            // Note : PAS de soft delete (immutabilité comptable)
            $table->timestamps();

            // ============================================
            // 9. INDEX
            // ============================================
            // Index principal : historique des transactions d'un vendeur
            $table->index(['seller_id', 'created_at'], 'idx_seller_txn_seller_date');

            // Index par type : stats CA, commissions, refunds
            $table->index(['seller_id', 'type'], 'idx_seller_txn_seller_type');

            // Index par direction : calcul du solde (SUM credits - SUM debits)
            $table->index(['seller_id', 'direction'], 'idx_seller_txn_seller_direction');

            // Index par commande : audit d'une commande spécifique
            $table->index('order_id', 'idx_seller_txn_order');

            // Index par item : audit d'un item spécifique
            $table->index('order_item_id', 'idx_seller_txn_order_item');

            // Index par payout : audit d'un versement
            $table->index('payout_id', 'idx_seller_txn_payout');
        });
    }

    /**
     * Supprime la table `seller_transactions`.
     *
     * ⚠️ Action TRÈS destructive :
     *   - Tout l'historique comptable vendeur est perdu
     *   - Les soldes dans `seller_profiles` deviennent incohérents
     *   - À n'exécuter qu'en environnement de développement
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_transactions');
    }
};