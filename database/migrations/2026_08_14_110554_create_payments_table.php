<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : payments
     * 
     * Rôle : Paiements des commandes
     * Relations :
     *   - Many-to-One avec orders (plusieurs paiements = 1 commande)
     *   - Many-to-One avec users (plusieurs paiements = 1 utilisateur)
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 📝 Référence unique du paiement
            $table->string('payment_number', 50)->unique()->comment("Numéro unique du paiement");
            
            // 🔗 Clé étrangère vers orders
            $table->foreignId('order_id')
                  ->constrained('orders')
                  ->onDelete('cascade')
                  ->comment("Commande associée");
            
            // 🔗 Clé étrangère vers users
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->comment("Utilisateur payeur");
            
            // 💰 Montants
            $table->decimal('amount', 12, 2)->comment("Montant du paiement");
            $table->decimal('fee', 12, 2)->default(0)->comment("Frais de transaction");
            $table->decimal('total_amount', 12, 2)->comment("Montant total débité");
            $table->string('currency', 10)->default('FCFA')->comment("Devise du paiement");
            
            // 💳 Méthode de paiement
            $table->enum('method', [
                'card',          // Carte bancaire
                'paypal',        // PayPal
                'mobile_money',  // Mobile Money
                'bank_transfer', // Virement bancaire
                'cash_on_delivery', // Paiement à la livraison
                'wallet',        // Portefeuille électronique
                'points'         // Points de fidélité
            ])->comment("Méthode de paiement");
            
            // 📊 Statut du paiement
            $table->enum('status', [
                'pending',       // En attente
                'processing',    // En cours
                'completed',     // Complété
                'failed',        // Échoué
                'cancelled',     // Annulé
                'refunded',      // Remboursé
                'partially_refunded' // Partiellement remboursé
            ])->default('pending')->comment("Statut du paiement");
            
            // 🔑 Références externes
            $table->string('transaction_id', 150)->nullable()->comment("ID de transaction externe");
            $table->string('gateway_reference', 150)->nullable()->comment("Référence de la passerelle");
            $table->string('authorization_code', 100)->nullable()->comment("Code d'autorisation");
            
            // 📝 Informations supplémentaires
            $table->text('response_data')->nullable()->comment("Réponse de la passerelle (JSON)");
            $table->text('error_message')->nullable()->comment("Message d'erreur si échec");
            
            // 📅 Dates
            $table->timestamp('paid_at')->nullable()->comment("Date du paiement réussi");
            $table->timestamp('failed_at')->nullable()->comment("Date de l'échec");
            $table->timestamp('refunded_at')->nullable()->comment("Date du remboursement");
            
            // ⏰ Horodatage
            $table->timestamps();
            $table->softDeletes();
            
            // 🔍 Index
            $table->index('order_id', 'idx_payments_order');
            $table->index('user_id', 'idx_payments_user');
            $table->index('status', 'idx_payments_status');
            $table->index('method', 'idx_payments_method');
            $table->index('transaction_id', 'idx_payments_transaction');
            $table->index(['order_id', 'status'], 'idx_payments_order_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};