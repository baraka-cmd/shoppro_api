<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : review_images
     * 
     * Rôle : Images attachées aux avis des utilisateurs
     * Relation : Many-to-One avec reviews (plusieurs images = 1 avis)
     */
    public function up(): void
    {
        Schema::create('review_images', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 🔗 Clé étrangère vers reviews
            $table->foreignId('review_id')
                  ->constrained('reviews')
                  ->onDelete('cascade')
                  ->comment("Avis associé à l'image");
            
            // 🖼️ Informations de l'image
            $table->string('image_path', 255)->comment("Chemin de l'image");
            $table->string('thumbnail_path', 255)->nullable()->comment("Chemin de la miniature");
            $table->string('alt_text', 255)->nullable()->comment("Texte alternatif");
            $table->string('caption', 255)->nullable()->comment("Légende de l'image");
            
            // 📏 Dimensions
            $table->integer('width')->nullable()->comment("Largeur en pixels");
            $table->integer('height')->nullable()->comment("Hauteur en pixels");
            $table->integer('file_size')->nullable()->comment("Taille en octets");
            
            // 📝 Type d'image
            $table->enum('type', ['image', 'video'])
                  ->default('image')
                  ->comment("Type de média");
            
            // 📊 Position
            $table->integer('position')->default(0)->comment("Ordre d'affichage");
            
            // ✅ Statut
            $table->boolean('is_active')->default(true)->comment("Image active");
            
            // ⏰ Horodatage
            $table->timestamps();
            
            // 🔍 Index
            $table->index('review_id', 'idx_review_images_review');
            $table->index('position', 'idx_review_images_position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_images');
    }
};