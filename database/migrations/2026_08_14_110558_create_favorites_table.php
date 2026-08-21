<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : favorites
     * 
     * Rôle : Produits favoris des utilisateurs (liste de souhaits)
     * Relations :
     *   - Many-to-One avec users (plusieurs favoris = 1 utilisateur)
     *   - Many-to-One avec products (plusieurs favoris = 1 produit)
     */
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 🔗 Clé étrangère vers users
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->comment("Utilisateur ayant ajouté aux favoris");
            
            // 🔗 Clé étrangère vers products
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->onDelete('cascade')
                  ->comment("Produit favori");
            
            // 📝 Notes de l'utilisateur
            $table->string('note', 255)->nullable()->comment("Note personnelle sur le favori");
            
            // 🏷️ Collection personnalisée
            $table->string('collection_name', 100)->nullable()->comment("Nom de la collection (ex: Noël)");
            
            // 📊 Rappels
            $table->boolean('price_alert')->default(false)->comment("Alerte de baisse de prix");
            $table->decimal('price_alert_threshold', 12, 2)->nullable()->comment("Prix cible pour l'alerte");
            
            // 📅 Date d'ajout
            $table->timestamp('added_at')->useCurrent()->comment("Date d'ajout aux favoris");
            
            // ✅ Statut
            $table->boolean('is_active')->default(true)->comment("Favori actif");
            
            // ⏰ Horodatage
            $table->timestamps();
            
            // 🔍 Contrainte d'unicité (un utilisateur ne peut pas avoir deux fois le même favori)
            $table->unique(['user_id', 'product_id'], 'unique_user_product_favorite');
            
            // 🔍 Index
            $table->index('user_id', 'idx_favorites_user');
            $table->index('product_id', 'idx_favorites_product');
            $table->index('collection_name', 'idx_favorites_collection');
            $table->index(['user_id', 'is_active'], 'idx_favorites_user_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};