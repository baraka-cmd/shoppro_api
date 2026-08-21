<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : product_tag (PIVOT)
     * 
     * Rôle : Table pivot pour la relation Many-to-Many entre products et tags
     * Relation : 
     *   - products (N) ↔ (N) tags
     */
    public function up(): void
    {
        Schema::create('product_tag', function (Blueprint $table) {
            // 🔑 Clé primaire composée
            $table->id();
            
            // 🔗 Clé étrangère vers products
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->onDelete('cascade')
                  ->comment("Produit associé au tag");
            
            // 🔗 Clé étrangère vers tags
            $table->foreignId('tag_id')
                  ->constrained('tags')
                  ->onDelete('cascade')
                  ->comment("Tag associé au produit");
            
            // 📊 Métadonnées de la relation
            $table->integer('position')->default(0)->comment("Ordre d'affichage du tag sur le produit");
            $table->timestamp('assigned_at')->useCurrent()->comment("Date d'association");
            
            // ⏰ Horodatage
            $table->timestamps();
            
            // 🔍 Contrainte d'unicité (un produit ne peut pas avoir deux fois le même tag)
            $table->unique(['product_id', 'tag_id'], 'unique_product_tag');
            
            // 🔍 Index
            $table->index('product_id', 'idx_product_tag_product');
            $table->index('tag_id', 'idx_product_tag_tag');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_tag');
    }
};