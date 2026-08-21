<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : product_variants
     * 
     * Rôle : Variantes des produits (taille, couleur, etc.)
     * Relation : Many-to-One avec products (plusieurs variantes = 1 produit)
     */
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 🔗 Clé étrangère vers products
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->onDelete('cascade')
                  ->comment("Produit parent");
            
            // 🏷️ Informations de la variante
            $table->string('sku', 100)->unique()->comment("Code SKU unique de la variante");
            $table->string('barcode', 100)->nullable()->comment("Code-barres de la variante");
            $table->string('name', 255)->comment("Nom de la variante (ex: T-shirt Rouge M)");
            
            // 🎨 Attributs de la variante
            $table->string('color', 50)->nullable()->comment("Couleur (ex: Rouge, Bleu)");
            $table->string('color_code', 20)->nullable()->comment("Code couleur hexadécimal (#FF0000)");
            $table->string('size', 30)->nullable()->comment("Taille (S, M, L, XL)");
            $table->string('weight', 50)->nullable()->comment("Poids (ex: 500g, 1kg)");
            $table->string('material', 100)->nullable()->comment("Matière (ex: Coton, Cuir)");
            
            // 💰 Prix spécifique à la variante
            $table->decimal('price', 12, 2)->nullable()->comment("Prix de la variante");
            $table->decimal('discount_price', 12, 2)->nullable()->comment("Prix réduit de la variante");
            
            // 📦 Stock spécifique
            $table->integer('stock')->default(0)->comment("Stock de la variante");
            $table->integer('min_stock')->default(5)->comment("Seuil d'alerte stock");
            
            // 🖼️ Image de la variante
            $table->string('image')->nullable()->comment("Image de la variante");
            
            // ✅ Statut
            $table->boolean('is_active')->default(true)->comment("Variante active");
            $table->boolean('is_default')->default(false)->comment("Variante par défaut");
            
            // 📊 Position
            $table->integer('position')->default(0)->comment("Ordre d'affichage");
            
            // ⏰ Horodatage
            $table->timestamps();
            $table->softDeletes();
            
            // 🔍 Index
            $table->index('product_id', 'idx_variants_product');
            $table->index('color', 'idx_variants_color');
            $table->index('size', 'idx_variants_size');
            $table->index('price', 'idx_variants_price');
            $table->index(['product_id', 'color', 'size'], 'idx_variants_product_color_size');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};