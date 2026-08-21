<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : carts
     * 
     * Rôle : Paniers d'achat des utilisateurs
     * Relations :
     *   - Many-to-One avec users (plusieurs paniers = 1 utilisateur)
     *   - One-to-Many avec cart_items (1 panier = plusieurs éléments)
     */
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 🔗 Clé étrangère vers users (nullable pour les visiteurs)
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->comment("Utilisateur propriétaire du panier");
            
            // 🔑 Identifiant de session (pour les visiteurs non connectés)
            $table->string('session_id', 100)->nullable()->comment("Session ID pour visiteurs");
            
            // 💰 Totaux
            $table->decimal('subtotal', 12, 2)->default(0)->comment("Sous-total avant réductions");
            $table->decimal('discount', 12, 2)->default(0)->comment("Montant des réductions");
            $table->decimal('tax', 12, 2)->default(0)->comment("Montant des taxes");
            $table->decimal('shipping_fee', 12, 2)->default(0)->comment("Frais de livraison");
            $table->decimal('total', 12, 2)->default(0)->comment("Total final à payer");
            
            // 🎟️ Code promo appliqué
            $table->string('coupon_code', 50)->nullable()->comment("Code promo appliqué");
            $table->decimal('coupon_discount', 12, 2)->default(0)->comment("Réduction du code promo");
            
            // 📊 Statistiques
            $table->integer('items_count')->default(0)->comment("Nombre d'articles dans le panier");
            $table->integer('total_items')->default(0)->comment("Quantité totale d'articles");
            
            // 💱 Devise
            $table->string('currency', 10)->default('FCFA')->comment("Devise du panier");
            
            // 📅 Dates
            $table->timestamp('last_activity_at')->nullable()->comment("Dernière activité");
            $table->timestamp('abandoned_at')->nullable()->comment("Date d'abandon du panier");
            
            // ✅ Statut
            $table->boolean('is_active')->default(true)->comment("Panier actif");
            $table->boolean('is_abandoned')->default(false)->comment("Panier abandonné");
            $table->boolean('is_converted')->default(false)->comment("Converti en commande");
            
            // ⏰ Horodatage
            $table->timestamps();
            $table->softDeletes();
            
            // 🔍 Index
            $table->index('user_id', 'idx_carts_user');
            $table->index('session_id', 'idx_carts_session');
            $table->index('is_active', 'idx_carts_active');
            $table->index('is_abandoned', 'idx_carts_abandoned');
            $table->index(['user_id', 'is_active'], 'idx_carts_user_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};