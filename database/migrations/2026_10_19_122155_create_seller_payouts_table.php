<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crée la table `seller_payouts`.
     *
     * Rôle :
     *   - Gérer les demandes de retrait vendeur
     *   - Suivre le workflow de versement
     *   - Tracer les transactions bancaires / mobile money
     *
     * Workflow :
     *   pending → processing → completed
     *                        ↘ failed
     *                        ↘ cancelled
     *
     * ⚠️ Choix d'architecture :
     *   - Table JAMAIS soft-deleted (traçabilité financière)
     *   - Référence UNIQUE lisible (PAYOUT-YYYYMMDD-XXXXXX)
     *   - Liée à plusieurs `seller_transactions` (via payout_id)
     *
     * Règle métier :
     *   - Un vendeur ne peut avoir qu'UN SEUL payout 'pending' ou 'processing' à la fois
     *   - Le montant demandé doit être ≤ seller_profiles.balance
     *   - Le solde est débité au passage à 'completed'
     *
     * Relations :
     *   - Many-to-One avec `users` (seller)
     *   - One-to-Many avec `seller_transactions` (un payout = plusieurs transactions liées)
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('seller_payouts', function (Blueprint $table) {
            // ============================================
            // 1. CLÉ PRIMAIRE
            // ============================================
            $table->id();

            // ============================================
            // 2. RÉFÉRENCE UNIQUE (audit)
            // ============================================
            $table->string('reference', 50)
                  ->unique()
                  ->comment('Référence unique du payout (PAYOUT-YYYYMMDD-XXXXXX)');

            // ============================================
            // 3. RELATION VENDEUR
            // ============================================
            $table->foreignId('seller_id')
                  ->constrained('users')
                  ->cascadeOnDelete()
                  ->comment('Vendeur demandeur du retrait');

            // ============================================
            // 4. MONTANT
            // ============================================
            $table->decimal('amount', 15, 2)
                  ->comment('Montant demandé en retrait');

            $table->decimal('fee', 15, 2)
                  ->default(0)
                  ->comment('Frais de retrait (mobile money, virement, etc.)');

            $table->decimal('net_amount', 15, 2)
                  ->comment('Montant net à verser (amount - fee)');

            $table->string('currency', 10)
                  ->default('FCFA')
                  ->comment('Devise du paiement');

            // ============================================
            // 5. MÉTHODE DE PAIEMENT
            // ============================================
            $table->string('method', 50)
                  ->comment('Méthode de retrait : mobile_money, bank_transfer, paypal, cash');

            $table->string('account', 100)
                  ->comment('Compte destinataire (numéro, IBAN, email)');

            $table->string('account_name', 150)
                  ->nullable()
                  ->comment('Nom du titulaire du compte');

            // ============================================
            // 6. STATUT & WORKFLOW
            // ============================================
            $table->enum('status', [
                'pending',     // En attente de traitement
                'processing',  // En cours de traitement (virement lancé)
                'completed',   // Versement réussi
                'failed',      // Échec (compte invalide, etc.)
                'cancelled',   // Annulé par vendeur ou admin
            ])
            ->default('pending')
            ->comment('Statut du payout');

            // ============================================
            // 7. NOTES
            // ============================================
            $table->text('note')
                  ->nullable()
                  ->comment('Note du vendeur au moment de la demande');

            $table->text('admin_note')
                  ->nullable()
                  ->comment('Note de l\'admin (raison du refus, référence bancaire, etc.)');

            $table->text('failure_reason')
                  ->nullable()
                  ->comment('Raison de l\'échec (si status = failed)');

            // ============================================
            // 8. RÉFÉRENCES EXTERNES (audit bancaire)
            // ============================================
            $table->string('external_reference', 100)
                  ->nullable()
                  ->comment('Référence externe (ID transaction mobile money, virement bancaire)');

            $table->string('external_provider', 50)
                  ->nullable()
                  ->comment('Fournisseur externe : mtn_momo, orange_money, stripe, etc.');

            // ============================================
            // 9. TIMESTAMPS DU WORKFLOW
            // ============================================
            $table->timestamp('requested_at')
                  ->nullable()
                  ->comment('Date de la demande');

            $table->timestamp('processed_at')
                  ->nullable()
                  ->comment('Date de début de traitement');

            $table->timestamp('completed_at')
                  ->nullable()
                  ->comment('Date de versement effectif');

            $table->timestamp('failed_at')
                  ->nullable()
                  ->comment('Date de l\'échec');

            $table->timestamp('cancelled_at')
                  ->nullable()
                  ->comment('Date d\'annulation');

            // ============================================
            // 10. RELATIONS ADMIN
            // ============================================
            $table->foreignId('processed_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->comment('Admin ayant traité le payout');

            // ============================================
            // 11. HORODATAGE
            // ============================================
            // Note : PAS de soft delete (traçabilité financière)
            $table->timestamps();

            // ============================================
            // 12. INDEX
            // ============================================
            // Historique des payouts d'un vendeur
            $table->index(['seller_id', 'created_at'], 'idx_seller_payouts_seller_date');

            // Filtrer par statut (dashboard admin)
            $table->index(['status', 'created_at'], 'idx_seller_payouts_status_date');

            // Payouts en attente d'un vendeur spécifique
            $table->index(['seller_id', 'status'], 'idx_seller_payouts_seller_status');

            // Filtrer par méthode (stats des moyens de paiement)
            $table->index('method', 'idx_seller_payouts_method');
        });
    }

    /**
     * Supprime la table `seller_payouts`.
     *
     * ⚠️ Action TRÈS destructive :
     *   - Tout l'historique des versements est perdu
     *   - Les transactions liées auront `payout_id = NULL`
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_payouts');
    }
};