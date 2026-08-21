<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : subcategories
     * 
     * Rôle : Sous-catégories détaillées
     * Relation : One-to-Many avec categories (1 catégorie = plusieurs sous-catégories)
     */
    public function up(): void
    {
        Schema::create('subcategories', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 🔗 Clé étrangère vers categories
            $table->foreignId('category_id')
                  ->constrained('categories')
                  ->onDelete('cascade')
                  ->comment('Catégorie parente');
            
            // 📝 Informations de base
            $table->string('name', 150)->comment('Nom de la sous-catégorie');
            $table->string('slug', 180)->unique()->comment('Slug URL unique');
            $table->text('description')->nullable()->comment('Description');
            
            // 🖼️ Image
            $table->string('image')->nullable()->comment('Image de la sous-catégorie');
            $table->string('icon', 100)->nullable()->comment('Icône');
            
            // 📊 Position pour le tri
            $table->integer('position')->default(0)->comment('Ordre d\'affichage');
            
            // ✅ Statut
            $table->boolean('is_active')->default(true)->comment('Sous-catégorie active');
            
            // 🔍 SEO
            $table->string('meta_title', 200)->nullable()->comment('Titre SEO');
            $table->text('meta_description')->nullable()->comment('Description SEO');
            
            // ⏰ Horodatage
            $table->timestamps();
            $table->softDeletes();
            
            // 🔍 Index
            $table->index('category_id', 'idx_subcategories_category');
            $table->index('position', 'idx_subcategories_position');
            $table->index(['category_id', 'is_active'], 'idx_subcat_category_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subcategories');
    }
};