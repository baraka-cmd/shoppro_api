<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : transactions
     * 
     * Rôle : Journal détaillé de toutes les transactions
     * Relations :
     *   - Many-to-One avec users (plusieurs transactions = 1 utilisateur)
     *   - Many-to-One avec orders (plusieurs transactions = 1 commande)
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 📝 Référence unique
            $table->string('transaction_number', 50)->unique()->comment("Numéro unique de transaction");
            
            // 🔗 Clé étrangère vers users
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->comment("Utilisateur concerné");
            
            // 🔗 Clé étrangère vers orders (nullable pour les recharges wallet)
            $table->foreignId('order_id')
                  ->nullable()
                  ->constrained('orders')
                  ->onDelete('set null')
                  ->comment("Commande associée");
            
            // 💰 Montants
            $table->decimal('amount', 12, 2)->comment("Montant de la transaction");
            $table->decimal('fee', 12, 2)->default(0)->comment("Frais de transaction");
            $table->decimal('net_amount', 12, 2)->comment("Montant net après frais");
            $table->string('currency', 10)->default('FCFA')->comment("Devise");
            
            // 📊 Type de transaction
            $table->enum('type', [
                'payment',       // Paiement
                'refund',        // Remboursement
                'wallet_topup',  // Recharge portefeuille
                'withdrawal',    // Retrait
                'transfer',      // Transfert
                'commission'     // Commission
            ])->comment("Type de transaction");
            
            // 📊 Statut
            $table->enum('status', [
                'pending',       // En attente
                'processing',    // En cours
                'completed',     // Complétée
                'failed',        // Échouée
                'cancelled'      // Annulée
            ])->default('pending')->comment("Statut de la transaction");
            
            // 💳 Méthode de paiement
            $table->string('payment_method', 100)->nullable()->comment("Méthode utilisée");
            $table->string('payment_gateway', 100)->nullable()->comment("Passerelle de paiement");
            
            // 🔑 Références
            $table->string('gateway_transaction_id', 150)->nullable()->comment("ID chez le prestataire");
            $table->string('gateway_reference', 150)->nullable()->comment("Référence prestataire");
            
            // 📝 Détails
            $table->text('description')->nullable()->comment("Description de la transaction");
            $table->text('metadata')->nullable()->comment("Données supplémentaires (JSON)");
            $table->text('error_message')->nullable()->comment("Message d'erreur si échec");
            
            // 📅 Dates
            $table->timestamp('completed_at')->nullable()->comment("Date de complétion");
            $table->timestamp('failed_at')->nullable()->comment("Date d'échec");
            
            // ⏰ Horodatage
            $table->timestamps();
            
            // 🔍 Index
            $table->index('user_id', 'idx_transactions_user');
            $table->index('order_id', 'idx_transactions_order');
            $table->index('type', 'idx_transactions_type');
            $table->index('status', 'idx_transactions_status');
            $table->index('created_at', 'idx_transactions_date');
            $table->index(['user_id', 'type'], 'idx_transactions_user_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};