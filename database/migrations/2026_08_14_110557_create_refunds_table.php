<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : refunds
     * 
     * Rôle : Remboursements des paiements
     * Relations :
     *   - Many-to-One avec payments (plusieurs remboursements = 1 paiement)
     *   - Many-to-One avec orders (plusieurs remboursements = 1 commande)
     */
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 📝 Référence unique
            $table->string('refund_number', 50)->unique()->comment("Numéro unique de remboursement");
            
            // 🔗 Clé étrangère vers payments
            $table->foreignId('payment_id')
                  ->constrained('payments')
                  ->onDelete('cascade')
                  ->comment("Paiement remboursé");
            
            // 🔗 Clé étrangère vers orders
            $table->foreignId('order_id')
                  ->constrained('orders')
                  ->onDelete('cascade')
                  ->comment("Commande concernée");
            
            // 🔗 Clé étrangère vers users
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->comment("Utilisateur remboursé");
            
            // 💰 Montants
            $table->decimal('amount', 12, 2)->comment("Montant du remboursement");
            $table->decimal('fee_refunded', 12, 2)->default(0)->comment("Frais remboursés");
            $table->decimal('total_refunded', 12, 2)->comment("Total remboursé");
            
            // 📊 Type de remboursement
            $table->enum('type', [
                'full',          // Remboursement total
                'partial',       // Remboursement partiel
                'shipping_only'  // Frais de livraison uniquement
            ])->comment("Type de remboursement");
            
            // 📊 Statut
            $table->enum('status', [
                'pending',       // En attente
                'processing',    // En cours
                'completed',     // Complété
                'failed',        // Échoué
                'cancelled'      // Annulé
            ])->default('pending')->comment("Statut du remboursement");
            
            // 📝 Raison
            $table->string('reason', 255)->comment("Raison du remboursement");
            $table->text('note')->nullable()->comment("Note détaillée");
            
            // 🔑 Références externes
            $table->string('gateway_refund_id', 150)->nullable()->comment("ID de remboursement prestataire");
            
            // 📅 Dates
            $table->timestamp('processed_at')->nullable()->comment("Date de traitement");
            $table->timestamp('completed_at')->nullable()->comment("Date de complétion");
            
            // ⏰ Horodatage
            $table->timestamps();
            
            // 🔍 Index
            $table->index('payment_id', 'idx_refunds_payment');
            $table->index('order_id', 'idx_refunds_order');
            $table->index('user_id', 'idx_refunds_user');
            $table->index('status', 'idx_refunds_status');
            $table->index('type', 'idx_refunds_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};