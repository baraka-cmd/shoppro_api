<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : product_images
     * 
     * Rôle : Images multiples pour chaque produit
     * Relation : Many-to-One avec products (plusieurs images = 1 produit)
     */
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 🔗 Clé étrangère vers products
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->onDelete('cascade')
                  ->comment("Produit associé");
            
            // 🖼️ Informations de l'image
            $table->string('image_path', 255)->comment("Chemin de l'image");
            $table->string('thumbnail_path', 255)->nullable()->comment("Chemin de la miniature");
            $table->string('alt_text', 255)->nullable()->comment("Texte alternatif (accessibilité)");
            $table->string('caption', 255)->nullable()->comment("Légende de l'image");
            
            // 📊 Position et priorité
            $table->integer('position')->default(0)->comment("Ordre d'affichage");
            $table->boolean('is_primary')->default(false)->comment("Image principale");
            
            // 📏 Dimensions
            $table->integer('width')->nullable()->comment("Largeur en pixels");
            $table->integer('height')->nullable()->comment("Hauteur en pixels");
            $table->integer('file_size')->nullable()->comment("Taille du fichier en octets");
            
            // 📝 Type d'image
            $table->enum('type', ['image', 'video', 'gif'])
                  ->default('image')
                  ->comment("Type de média");
            
            // ✅ Statut
            $table->boolean('is_active')->default(true)->comment("Image active");
            
            // ⏰ Horodatage
            $table->timestamps();
            
            // 🔍 Index
            $table->index('product_id', 'idx_product_images_product');
            $table->index('is_primary', 'idx_product_images_primary');
            $table->index(['product_id', 'position'], 'idx_product_images_position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};