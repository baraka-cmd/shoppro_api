<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : products
     * 
     * Rôle : Table centrale des produits
     * Relations :
     *   - Many-to-One avec categories (plusieurs produits = 1 catégorie)
     *   - Many-to-One avec brands (plusieurs produits = 1 marque)
     *   - One-to-Many avec product_images (1 produit = plusieurs images)
     *   - One-to-Many avec product_variants (1 produit = plusieurs variantes)
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 📝 Informations de base
            $table->string('name', 255)->comment("Nom complet du produit");
            $table->string('slug', 280)->unique()->comment("Slug URL unique pour le produit");
            $table->text('short_description')->nullable()->comment("Description courte (résumé)");
            $table->longText('description')->nullable()->comment("Description détaillée du produit");
            
            // 💰 Prix et réductions
            $table->decimal('price', 12, 2)->comment("Prix normal du produit");
            $table->decimal('discount_price', 12, 2)->nullable()->comment("Prix réduit (promotion)");
            $table->decimal('cost_price', 12, 2)->nullable()->comment("Prix d'achat (pour calculer les bénéfices)");
            $table->decimal('tax_rate', 5, 2)->default(0)->comment("Taux de taxe en pourcentage");
            
            // 📦 Stock
            $table->integer('stock')->default(0)->comment("Quantité en stock");
            $table->integer('min_stock')->default(5)->comment("Seuil d'alerte stock minimum");
            $table->integer('max_stock')->nullable()->comment("Stock maximum autorisé");
            $table->string('sku', 100)->unique()->comment("Code SKU unique (Stock Keeping Unit)");
            $table->string('barcode', 100)->nullable()->comment("Code-barres EAN/UPC");
            
            // 🔗 Relations
            $table->foreignId('category_id')
                  ->constrained('categories')
                  ->onDelete('cascade')
                  ->comment("Catégorie du produit");
            
            $table->foreignId('subcategory_id')
                  ->nullable()
                  ->constrained('subcategories')
                  ->onDelete('set null')
                  ->comment("Sous-catégorie du produit");
            
            $table->foreignId('brand_id')
                  ->nullable()
                  ->constrained('brands')
                  ->onDelete('set null')
                  ->comment("Marque du produit");
            
            // 🖼️ Image principale
            $table->string('main_image')->nullable()->comment("Image principale du produit");
            
            // 📊 Métriques
            $table->integer('views_count')->default(0)->comment("Nombre de vues");
            $table->integer('sales_count')->default(0)->comment("Nombre de ventes");
            $table->integer('favorites_count')->default(0)->comment("Nombre de favoris");
            $table->decimal('average_rating', 3, 2)->default(0)->comment("Note moyenne (0 à 5)");
            $table->integer('reviews_count')->default(0)->comment("Nombre d'avis");
            
            // ✅ Statut
            $table->boolean('is_active')->default(true)->comment("Produit visible en boutique");
            $table->boolean('is_featured')->default(false)->comment("Produit mis en avant");
            $table->boolean('is_new')->default(false)->comment("Produit nouveau");
            $table->boolean('is_bestseller')->default(false)->comment("Meilleure vente");
            $table->boolean('is_on_sale')->default(false)->comment("En promotion");
            $table->boolean('is_digital')->default(false)->comment("Produit numérique (téléchargeable)");
            
            // 📅 Disponibilité
            $table->date('available_from')->nullable()->comment("Date de disponibilité");
            $table->date('available_until')->nullable()->comment("Date de fin de disponibilité");
            
            // 🔍 SEO
            $table->string('meta_title', 200)->nullable()->comment("Titre SEO");
            $table->text('meta_description')->nullable()->comment("Description SEO");
            $table->string('meta_keywords', 255)->nullable()->comment("Mots-clés SEO");
            
            // ⏰ Horodatage
            $table->timestamps();
            $table->softDeletes();
            
            // 🔍 Index pour optimiser les recherches
            $table->index('name', 'idx_products_name');
            $table->index('price', 'idx_products_price');
            $table->index('category_id', 'idx_products_category');
            $table->index('brand_id', 'idx_products_brand');
            $table->index('is_active', 'idx_products_active');
            $table->index('is_featured', 'idx_products_featured');
            $table->index('average_rating', 'idx_products_rating');
            $table->index(['category_id', 'is_active'], 'idx_products_category_active');
            $table->index(['price', 'discount_price'], 'idx_products_prices');
            $table->fullText(['name', 'description'], 'idx_products_fulltext');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};