<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : banners
     * 
     * Rôle : Bannières publicitaires de l'application
     * Relation : Aucune relation directe (peut pointer vers un produit ou catégorie)
     */
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 📝 Informations de base
            $table->string('title', 150)->comment("Titre de la bannière");
            $table->string('subtitle', 255)->nullable()->comment("Sous-titre");
            $table->text('description')->nullable()->comment("Description");
            
            // 🖼️ Images
            $table->string('image', 255)->comment("Image principale");
            $table->string('image_mobile', 255)->nullable()->comment("Image mobile (plus petite)");
            $table->string('thumbnail', 255)->nullable()->comment("Miniature");
            
            // 🔗 Lien de redirection
            $table->enum('target_type', [
                'product',       // Produit
                'category',      // Catégorie
                'promotion',     // Promotion
                'external_url',  // URL externe
                'none'           // Pas de lien
            ])->default('none')->comment("Type de cible");
            $table->string('target_id', 100)->nullable()->comment("ID de la cible");
            $table->string('external_url', 255)->nullable()->comment("URL externe");
            
            // 📊 Position
            $table->enum('position', [
                'home_top',      // Haut de page d'accueil
                'home_middle',   // Milieu de page
                'home_bottom',   // Bas de page
                'category_page', // Page catégorie
                'product_page',  // Page produit
                'sidebar'        // Barre latérale
            ])->default('home_top')->comment("Position de la bannière");
            
            // 📊 Ordre
            $table->integer('sort_order')->default(0)->comment("Ordre d'affichage");
            
            // 📅 Dates d'affichage
            $table->date('start_date')->nullable()->comment("Date de début d'affichage");
            $table->date('end_date')->nullable()->comment("Date de fin d'affichage");
            
            // ✅ Statut
            $table->boolean('is_active')->default(true)->comment("Bannière active");
            $table->boolean('is_animated')->default(false)->comment("Bannière animée");
            
            // ⏰ Horodatage
            $table->timestamps();
            $table->softDeletes();
            
            // 🔍 Index
            $table->index('position', 'idx_banners_position');
            $table->index('is_active', 'idx_banners_active');
            $table->index('sort_order', 'idx_banners_sort');
            $table->index(['position', 'is_active'], 'idx_banners_position_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};