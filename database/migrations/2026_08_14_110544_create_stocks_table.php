<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : stocks
     * 
     * Rôle : Stock des produits par entrepôt
     * Relations :
     *   - Many-to-One avec products (plusieurs stocks = 1 produit)
     *   - Many-to-One avec warehouses (plusieurs stocks = 1 entrepôt)
     */
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 🔗 Clé étrangère vers products
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->onDelete('cascade')
                  ->comment("Produit concerné");
            
            // 🔗 Clé étrangère vers warehouses
            $table->foreignId('warehouse_id')
                  ->constrained('warehouses')
                  ->onDelete('cascade')
                  ->comment("Entrepôt de stockage");
            
            // 🔗 Clé étrangère vers product_variants (optionnel)
            $table->foreignId('product_variant_id')
                  ->nullable()
                  ->constrained('product_variants')
                  ->onDelete('cascade')
                  ->comment("Variante du produit (null si produit sans variante)");
            
            // 📦 Quantités
            $table->integer('quantity')->default(0)->comment("Quantité en stock");
            $table->integer('reserved_quantity')->default(0)->comment("Quantité réservée (commandes en cours)");
            $table->integer('available_quantity')->default(0)->comment("Quantité disponible à la vente");
            $table->integer('min_quantity')->default(5)->comment("Seuil d'alerte minimum");
            $table->integer('max_quantity')->nullable()->comment("Stock maximum autorisé");
            
            // 📍 Emplacement dans l'entrepôt
            $table->string('location', 100)->nullable()->comment("Emplacement (ex: Allée A, Rayon 3)");
            $table->string('shelf', 50)->nullable()->comment("Étagère");
            $table->string('bin', 50)->nullable()->comment("Bac/Casier");
            
            // 💰 Valeur
            $table->decimal('unit_cost', 12, 2)->nullable()->comment("Coût unitaire d'achat");
            $table->decimal('total_value', 12, 2)->nullable()->comment("Valeur totale du stock");
            
            // 📅 Dates
            $table->date('last_counted_at')->nullable()->comment("Date du dernier inventaire");
            $table->date('expiry_date')->nullable()->comment("Date d'expiration (produits périssables)");
            
            // ✅ Statut
            $table->boolean('is_active')->default(true)->comment("Stock actif");
            
            // ⏰ Horodatage
            $table->timestamps();
            $table->softDeletes();
            
            // 🔍 Contrainte d'unicité (un produit ne peut être qu'une fois dans un entrepôt)
            $table->unique(
                ['product_id', 'warehouse_id', 'product_variant_id'], 
                'unique_product_warehouse_variant'
            );
            
            // 🔍 Index
            $table->index('product_id', 'idx_stocks_product');
            $table->index('warehouse_id', 'idx_stocks_warehouse');
            $table->index('quantity', 'idx_stocks_quantity');
            $table->index('available_quantity', 'idx_stocks_available');
            $table->index(['product_id', 'warehouse_id'], 'idx_stocks_product_warehouse');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};