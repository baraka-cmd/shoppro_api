<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : coupons
     * 
     * Rôle : Codes promo et coupons de réduction
     * Relation : One-to-Many avec orders (1 coupon = plusieurs commandes)
     */
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 📝 Informations de base
            $table->string('code', 50)->unique()->comment("Code promo unique (ex: PROMO20)");
            $table->string('name', 150)->comment("Nom du coupon");
            $table->text('description')->nullable()->comment("Description du coupon");
            
            // 💰 Type de réduction
            $table->enum('discount_type', [
                'percentage',    // Pourcentage (%)
                'fixed',         // Montant fixe
                'free_shipping'  // Livraison gratuite
            ])->comment("Type de réduction");
            
            // 💰 Valeur de la réduction
            $table->decimal('discount_value', 12, 2)->comment("Valeur de la réduction");
            $table->decimal('maximum_discount', 12, 2)->nullable()->comment("Réduction maximale");
            $table->decimal('minimum_order', 12, 2)->default(0)->comment("Commande minimum requise");
            
            // 📊 Limites d'utilisation
            $table->integer('usage_limit')->nullable()->comment("Nombre maximum d'utilisations");
            $table->integer('usage_per_user')->default(1)->comment("Utilisations par utilisateur");
            $table->integer('usage_count')->default(0)->comment("Nombre d'utilisations actuel");
            
            // 📅 Dates de validité
            $table->date('start_date')->comment("Date de début");
            $table->date('end_date')->comment("Date de fin");
            
            // 🎯 Restrictions
            $table->string('categories', 255)->nullable()->comment("Catégories concernées (JSON)");
            $table->string('products', 255)->nullable()->comment("Produits concernés (JSON)");
            $table->string('users', 255)->nullable()->comment("Utilisateurs spécifiques (JSON)");
            
            // ✅ Statut
            $table->boolean('is_active')->default(true)->comment("Coupon actif");
            $table->boolean('is_public')->default(true)->comment("Coupon public");
            
            // ⏰ Horodatage
            $table->timestamps();
            $table->softDeletes();
            
            // 🔍 Index
            $table->index('code', 'idx_coupons_code');
            $table->index('is_active', 'idx_coupons_active');
            $table->index('start_date', 'idx_coupons_start');
            $table->index('end_date', 'idx_coupons_end');
            $table->index(['is_active', 'start_date', 'end_date'], 'idx_coupons_active_dates');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};