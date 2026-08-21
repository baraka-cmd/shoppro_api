<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : reviews
     * 
     * Rôle : Avis et évaluations des produits par les utilisateurs
     * Relations :
     *   - Many-to-One avec users (plusieurs avis = 1 utilisateur)
     *   - Many-to-One avec products (plusieurs avis = 1 produit)
     */
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 🔗 Clé étrangère vers users
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->comment("Utilisateur ayant laissé l'avis");
            
            // 🔗 Clé étrangère vers products
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->onDelete('cascade')
                  ->comment("Produit concerné par l'avis");
            
            // 🔗 Clé étrangère vers orders (optionnel - pour vérifier l'achat)
            $table->foreignId('order_id')
                  ->nullable()
                  ->constrained('orders')
                  ->onDelete('set null')
                  ->comment("Commande associée (pour avis vérifié)");
            
            // ⭐ Note
            $table->integer('rating')->comment("Note de 1 à 5 étoiles");
            $table->integer('quality_rating')->nullable()->comment("Note qualité (1-5)");
            $table->integer('price_rating')->nullable()->comment("Note prix (1-5)");
            $table->integer('shipping_rating')->nullable()->comment("Note livraison (1-5)");
            
            // 📝 Contenu
            $table->string('title', 255)->nullable()->comment("Titre de l'avis");
            $table->text('content')->comment("Contenu détaillé de l'avis");
            
            // ✅ Achat vérifié
            $table->boolean('is_verified_purchase')->default(false)->comment("Achat vérifié");
            $table->boolean('is_recommended')->default(true)->comment("Recommande le produit");
            
            // 📊 Statistiques
            $table->integer('helpful_count')->default(0)->comment("Nombre de votes utiles");
            $table->integer('not_helpful_count')->default(0)->comment("Nombre de votes inutiles");
            $table->integer('views_count')->default(0)->comment("Nombre de vues de l'avis");
            
            // 📊 Statut de modération
            $table->enum('status', [
                'pending',       // En attente de modération
                'approved',      // Approuvé
                'rejected',      // Rejeté
                'spam'          // Spam
            ])->default('pending')->comment("Statut de l'avis");
            
            // 📝 Réponse du vendeur
            $table->text('admin_response')->nullable()->comment("Réponse de l'administrateur");
            $table->timestamp('admin_response_at')->nullable()->comment("Date de la réponse admin");
            
            // 📅 Dates
            $table->timestamp('approved_at')->nullable()->comment("Date d'approbation");
            
            // ⏰ Horodatage
            $table->timestamps();
            $table->softDeletes();
            
            // 🔍 Contrainte d'unicité (un utilisateur ne peut laisser qu'un avis par produit)
            $table->unique(['user_id', 'product_id'], 'unique_user_product_review');
            
            // 🔍 Index
            $table->index('product_id', 'idx_reviews_product');
            $table->index('user_id', 'idx_reviews_user');
            $table->index('rating', 'idx_reviews_rating');
            $table->index('status', 'idx_reviews_status');
            $table->index('is_verified_purchase', 'idx_reviews_verified');
            $table->index(['product_id', 'status'], 'idx_reviews_product_status');
            $table->index(['product_id', 'rating'], 'idx_reviews_product_rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};