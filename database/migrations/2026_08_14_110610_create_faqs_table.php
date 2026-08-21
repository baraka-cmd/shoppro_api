<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : faqs
     * 
     * Rôle : Questions fréquemment posées
     * Relation : Aucune relation directe
     */
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 📝 Informations de base
            $table->string('question', 255)->comment("Question posée");
            $table->text('answer')->comment("Réponse à la question");
            
            // 📊 Catégorie
            $table->enum('category', [
                'general',       // Général
                'orders',        // Commandes
                'shipping',      // Livraison
                'payments',      // Paiements
                'returns',       // Retours
                'account',       // Compte
                'products'       // Produits
            ])->default('general')->comment("Catégorie de la FAQ");
            
            // 📊 Statistiques
            $table->integer('views_count')->default(0)->comment("Nombre de vues");
            $table->integer('helpful_count')->default(0)->comment("Votes utiles");
            
            // 📊 Position et statut
            $table->integer('position')->default(0)->comment("Ordre d'affichage");
            $table->boolean('is_active')->default(true)->comment("FAQ active");
            $table->boolean('is_featured')->default(false)->comment("Mise en avant");
            
            // ⏰ Horodatage
            $table->timestamps();
            $table->softDeletes();
            
            // 🔍 Index
            $table->index('category', 'idx_faqs_category');
            $table->index('is_active', 'idx_faqs_active');
            $table->index('position', 'idx_faqs_position');
            $table->index(['category', 'is_active'], 'idx_faqs_category_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};