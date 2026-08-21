<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : tags
     * 
     * Rôle : Étiquettes pour catégoriser les produits
     * Relation : Many-to-Many avec products (via table pivot product_tag)
     */
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 📝 Informations de base
            $table->string('name', 100)->comment("Nom du tag (ex: Nouveau, Promo, Best-seller)");
            $table->string('slug', 120)->unique()->comment("Slug URL unique du tag");
            
            // 🎨 Apparence
            $table->string('color', 20)->nullable()->comment("Couleur du tag (ex: #FF0000)");
            $table->string('background_color', 20)->nullable()->comment("Couleur de fond");
            $table->string('icon', 100)->nullable()->comment("Icône du tag");
            
            // 📝 Description
            $table->text('description')->nullable()->comment("Description du tag");
            
            // 📊 Statistiques
            $table->integer('products_count')->default(0)->comment("Nombre de produits avec ce tag");
            
            // ✅ Statut
            $table->boolean('is_active')->default(true)->comment("Tag actif");
            
            // ⏰ Horodatage
            $table->timestamps();
            
            // 🔍 Index
            $table->index('name', 'idx_tags_name');
            $table->index('is_active', 'idx_tags_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};