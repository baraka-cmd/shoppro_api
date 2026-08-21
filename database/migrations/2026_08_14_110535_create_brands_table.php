<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : brands
     * 
     * Rôle : Marques des produits
     * Relation : One-to-Many avec products (1 marque = plusieurs produits)
     */
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 📝 Informations de base
            $table->string('name', 150)->comment("Nom de la marque");
            $table->string('slug', 180)->unique()->comment("Slug URL unique pour la marque");
            $table->text('description')->nullable()->comment("Description de la marque");
            
            // 🖼️ Logo et image
            $table->string('logo')->nullable()->comment("Logo de la marque");
            $table->string('banner')->nullable()->comment("Bannière de la marque");
            
            // 📞 Contact
            $table->string('website', 255)->nullable()->comment("Site web officiel");
            $table->string('email', 150)->nullable()->comment("Email de contact");
            $table->string('phone', 20)->nullable()->comment("Téléphone de contact");
            
            // 🌍 Origine
            $table->string('country', 100)->nullable()->comment("Pays d'origine de la marque");
            
            // 📊 Statistiques
            $table->integer('products_count')->default(0)->comment("Nombre de produits associés");
            $table->decimal('average_rating', 3, 2)->default(0)->comment("Note moyenne des produits");
            
            // ✅ Statut
            $table->boolean('is_active')->default(true)->comment("Marque active");
            $table->boolean('is_featured')->default(false)->comment("Marque mise en avant");
            
            // 🔍 SEO
            $table->string('meta_title', 200)->nullable()->comment("Titre SEO pour Google");
            $table->text('meta_description')->nullable()->comment("Description SEO pour Google");
            
            // ⏰ Horodatage
            $table->timestamps();
            $table->softDeletes();
            
            // 🔍 Index pour les recherches
            $table->index('name', 'idx_brands_name');
            $table->index('is_active', 'idx_brands_active');
            $table->index('is_featured', 'idx_brands_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};