<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : orders
     * 
     * Rôle : Commandes des utilisateurs
     * Relations :
     *   - Many-to-One avec users (plusieurs commandes = 1 utilisateur)
     *   - One-to-Many avec order_items (1 commande = plusieurs éléments)
     *   - One-to-Many avec payments (1 commande = plusieurs paiements)
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 📝 Numéro de commande unique
            $table->string('order_number', 50)->unique()->comment("Numéro unique de commande");
            
            // 🔗 Clé étrangère vers users
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->comment("Client ayant passé la commande");
            
            // 🔗 Clé étrangère vers user_addresses (adresse de livraison)
            $table->foreignId('shipping_address_id')
                  ->nullable()
                  ->constrained('user_addresses')
                  ->onDelete('set null')
                  ->comment("Adresse de livraison");
            
            // 🔗 Clé étrangère vers user_addresses (adresse de facturation)
            $table->foreignId('billing_address_id')
                  ->nullable()
                  ->constrained('user_addresses')
                  ->onDelete('set null')
                  ->comment("Adresse de facturation");
            
            // 💰 Montants
            $table->decimal('subtotal', 12, 2)->comment("Sous-total des articles");
            $table->decimal('discount', 12, 2)->default(0)->comment("Réduction totale");
            $table->decimal('coupon_discount', 12, 2)->default(0)->comment("Réduction code promo");
            $table->decimal('shipping_fee', 12, 2)->default(0)->comment("Frais de livraison");
            $table->decimal('tax', 12, 2)->default(0)->comment("Taxes");
            $table->decimal('total', 12, 2)->comment("Total final");
            
            // 📊 Statut de la commande
            $table->enum('status', [
                'pending',      // En attente
                'confirmed',    // Confirmée
                'processing',   // En préparation
                'shipped',      // Expédiée
                'delivered',    // Livrée
                'cancelled',    // Annulée
                'refunded',     // Remboursée
                'returned'      // Retournée
            ])->default('pending')->comment("Statut de la commande");
            
            // 💳 Statut du paiement
            $table->enum('payment_status', [
                'pending',      // En attente
                'paid',         // Payée
                'failed',       // Échouée
                'refunded',     // Remboursée
                'partially_refunded' // Partiellement remboursée
            ])->default('pending')->comment("Statut du paiement");
            
            // 🚚 Livraison
            $table->string('shipping_method', 100)->nullable()->comment("Mode de livraison");
            $table->string('tracking_number', 100)->nullable()->comment("Numéro de suivi");
            $table->date('estimated_delivery_date')->nullable()->comment("Date de livraison estimée");
            $table->timestamp('shipped_at')->nullable()->comment("Date d'expédition");
            $table->timestamp('delivered_at')->nullable()->comment("Date de livraison");
            
            // 🎟️ Code promo
            $table->string('coupon_code', 50)->nullable()->comment("Code promo utilisé");
            
            // 📝 Notes
            $table->text('customer_note')->nullable()->comment("Note du client");
            $table->text('admin_note')->nullable()->comment("Note de l'administrateur");
            
            // 💱 Devise
            $table->string('currency', 10)->default('FCFA')->comment("Devise de la commande");
            
            // 📅 Dates importantes
            $table->timestamp('cancelled_at')->nullable()->comment("Date d'annulation");
            $table->string('cancellation_reason', 255)->nullable()->comment("Raison de l'annulation");
            
            // ⏰ Horodatage
            $table->timestamps();
            $table->softDeletes();
            
            // 🔍 Index
            $table->index('user_id', 'idx_orders_user');
            $table->index('status', 'idx_orders_status');
            $table->index('payment_status', 'idx_orders_payment');
            $table->index('created_at', 'idx_orders_date');
            $table->index('tracking_number', 'idx_orders_tracking');
            $table->index(['user_id', 'status'], 'idx_orders_user_status');
            $table->index(['status', 'created_at'], 'idx_orders_status_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};