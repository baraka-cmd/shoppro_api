<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : categories
     * 
     * Rôle : Catégories principales des produits
     * Relation : 
     *   - One-to-Many avec products (1 catégorie = plusieurs produits)
     *   - Self-referencing (catégorie parente)
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 📝 Informations de base
            $table->string('name', 150)->comment('Nom de la catégorie');
            $table->string('slug', 180)->unique()->comment('Slug URL unique');
            $table->text('description')->nullable()->comment('Description de la catégorie');
            
            // 🖼️ Image
            $table->string('image')->nullable()->comment('Image de la catégorie');
            $table->string('icon', 100)->nullable()->comment('Icône (FontAwesome, etc.)');
            
            // 🌳 Arborescence (catégorie parente)
            $table->foreignId('parent_id')
                  ->nullable()
                  ->constrained('categories')
                  ->onDelete('set null')
                  ->comment('Catégorie parente (null si racine)');
            
            // 📊 Position pour le tri
            $table->integer('position')->default(0)->comment('Ordre d\'affichage');
            
            // ✅ Statut
            $table->boolean('is_active')->default(true)->comment('Catégorie active');
            $table->boolean('is_featured')->default(false)->comment('Mise en avant');
            
            // 🔍 SEO
            $table->string('meta_title', 200)->nullable()->comment('Titre SEO');
            $table->text('meta_description')->nullable()->comment('Description SEO');
            
            // ⏰ Horodatage
            $table->timestamps();
            $table->softDeletes();
            
            // 🔍 Index
            $table->index('parent_id', 'idx_categories_parent');
            $table->index('position', 'idx_categories_position');
            $table->index('is_active', 'idx_categories_active');
            $table->index(['parent_id', 'position'], 'idx_categories_parent_position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};