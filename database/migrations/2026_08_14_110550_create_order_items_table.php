<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : order_items
     * 
     * Rôle : Éléments individuels dans les commandes
     * Relations :
     *   - Many-to-One avec orders (plusieurs éléments = 1 commande)
     *   - Many-to-One avec products (plusieurs éléments = 1 produit)
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 🔗 Clé étrangère vers orders
            $table->foreignId('order_id')
                  ->constrained('orders')
                  ->onDelete('cascade')
                  ->comment("Commande parente");
            
            // 🔗 Clé étrangère vers products
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->onDelete('cascade')
                  ->comment("Produit commandé");
            
            // 🔗 Clé étrangère vers product_variants (optionnel)
            $table->foreignId('product_variant_id')
                  ->nullable()
                  ->constrained('product_variants')
                  ->onDelete('set null')
                  ->comment("Variante commandée");
            
            // 📦 Quantité
            $table->integer('quantity')->comment("Quantité commandée");
            
            // 💰 Prix (snapshot au moment de la commande)
            $table->decimal('unit_price', 12, 2)->comment("Prix unitaire au moment de la commande");
            $table->decimal('discount_price', 12, 2)->nullable()->comment("Prix réduit");
            $table->decimal('total_price', 12, 2)->comment("Prix total (quantité x prix)");
            
            // 📝 Détails du produit (snapshot)
            $table->string('product_name', 255)->comment("Nom du produit (copie)");
            $table->string('product_image', 255)->nullable()->comment("Image du produit (copie)");
            $table->string('product_sku', 100)->comment("SKU du produit (copie)");
            $table->string('product_barcode', 100)->nullable()->comment("Code-barres (copie)");
            
            // 🎨 Options de variante
            $table->string('variant_name', 255)->nullable()->comment("Nom de la variante");
            $table->string('variant_color', 50)->nullable()->comment("Couleur de la variante");
            $table->string('variant_size', 30)->nullable()->comment("Taille de la variante");
            
            // 📊 Statut de l'article
            $table->enum('item_status', [
                'pending',      // En attente
                'shipped',      // Expédié
                'delivered',    // Livré
                'cancelled',    // Annulé
                'returned'      // Retourné
            ])->default('pending')->comment("Statut de l'article");
            
            // 🔄 Retour
            $table->boolean('is_returned')->default(false)->comment("Article retourné");
            $table->date('return_date')->nullable()->comment("Date du retour");
            $table->string('return_reason', 255)->nullable()->comment("Raison du retour");
            
            // 💰 Remboursement
            $table->boolean('is_refunded')->default(false)->comment("Article remboursé");
            $table->decimal('refund_amount', 12, 2)->nullable()->comment("Montant remboursé");
            
            // ⏰ Horodatage
            $table->timestamps();
            
            // 🔍 Index
            $table->index('order_id', 'idx_order_items_order');
            $table->index('product_id', 'idx_order_items_product');
            $table->index('item_status', 'idx_order_items_status');
            $table->index(['order_id', 'product_id'], 'idx_order_items_order_product');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};