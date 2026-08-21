<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : cart_items
     * 
     * Rôle : Éléments individuels dans les paniers
     * Relations :
     *   - Many-to-One avec carts (plusieurs éléments = 1 panier)
     *   - Many-to-One avec products (plusieurs éléments = 1 produit)
     */
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 🔗 Clé étrangère vers carts
            $table->foreignId('cart_id')
                  ->constrained('carts')
                  ->onDelete('cascade')
                  ->comment("Panier parent");
            
            // 🔗 Clé étrangère vers products
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->onDelete('cascade')
                  ->comment("Produit ajouté au panier");
            
            // 🔗 Clé étrangère vers product_variants (optionnel)
            $table->foreignId('product_variant_id')
                  ->nullable()
                  ->constrained('product_variants')
                  ->onDelete('cascade')
                  ->comment("Variante du produit");
            
            // 📦 Quantité
            $table->integer('quantity')->default(1)->comment("Quantité du produit");
            
            // 💰 Prix au moment de l'ajout
            $table->decimal('unit_price', 12, 2)->comment("Prix unitaire au moment de l'ajout");
            $table->decimal('discount_price', 12, 2)->nullable()->comment("Prix réduit");
            $table->decimal('total_price', 12, 2)->comment("Prix total (quantité x prix)");
            
            // 📝 Détails du produit (snapshot)
            $table->string('product_name', 255)->nullable()->comment("Nom du produit (copie)");
            $table->string('product_image', 255)->nullable()->comment("Image du produit (copie)");
            $table->string('product_sku', 100)->nullable()->comment("SKU du produit (copie)");
            
            // 🎨 Options de variante
            $table->string('variant_name', 255)->nullable()->comment("Nom de la variante");
            $table->string('variant_color', 50)->nullable()->comment("Couleur de la variante");
            $table->string('variant_size', 30)->nullable()->comment("Taille de la variante");
            
            // 📝 Notes
            $table->text('note')->nullable()->comment("Note de l'utilisateur");
            
            // ✅ Statut
            $table->boolean('is_active')->default(true)->comment("Élément actif");
            $table->boolean('is_saved_for_later')->default(false)->comment("Gardé pour plus tard");
            
            // ⏰ Horodatage
            $table->timestamps();
            
            // 🔍 Contrainte d'unicité (même produit avec même variante = une seule ligne)
            $table->unique(
                ['cart_id', 'product_id', 'product_variant_id'], 
                'unique_cart_product_variant'
            );
            
            // 🔍 Index
            $table->index('cart_id', 'idx_cart_items_cart');
            $table->index('product_id', 'idx_cart_items_product');
            $table->index('is_saved_for_later', 'idx_cart_items_saved');
            $table->index(['cart_id', 'is_active'], 'idx_cart_items_cart_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};