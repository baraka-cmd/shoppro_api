<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : stock_movements
     * 
     * Rôle : Historique des mouvements de stock (entrées, sorties, ajustements)
     * Relations :
     *   - Many-to-One avec products (plusieurs mouvements = 1 produit)
     *   - Many-to-One avec warehouses (plusieurs mouvements = 1 entrepôt)
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 🔗 Clé étrangère vers products
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->onDelete('cascade')
                  ->comment("Produit concerné par le mouvement");
            
            // 🔗 Clé étrangère vers product_variants (optionnel)
            $table->foreignId('product_variant_id')
                  ->nullable()
                  ->constrained('product_variants')
                  ->onDelete('cascade')
                  ->comment("Variante concernée");
            
            // 🔗 Clé étrangère vers warehouses
            $table->foreignId('warehouse_id')
                  ->constrained('warehouses')
                  ->onDelete('cascade')
                  ->comment("Entrepôt concerné");
            
            // 🔗 Clé étrangère vers users (qui a fait le mouvement)
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null')
                  ->comment("Utilisateur ayant effectué le mouvement");
            
            // 📊 Type de mouvement
            $table->enum('type', ['in', 'out', 'adjustment', 'transfer', 'return', 'sale', 'purchase'])
                  ->comment("Type de mouvement");
            
            // 📦 Quantités
            $table->integer('quantity')->comment("Quantité du mouvement (positive ou négative)");
            $table->integer('quantity_before')->default(0)->comment("Quantité avant le mouvement");
            $table->integer('quantity_after')->default(0)->comment("Quantité après le mouvement");
            
            // 💰 Valeur
            $table->decimal('unit_price', 12, 2)->nullable()->comment("Prix unitaire du mouvement");
            $table->decimal('total_price', 12, 2)->nullable()->comment("Prix total du mouvement");
            
            // 📝 Référence
            $table->string('reference', 100)->nullable()->comment("Référence (numéro de commande, facture)");
            $table->text('note')->nullable()->comment("Note explicative");
            
            // ⏰ Horodatage
            $table->timestamps();
            
            // 🔍 Index
            $table->index('product_id', 'idx_stock_movements_product');
            $table->index('warehouse_id', 'idx_stock_movements_warehouse');
            $table->index('type', 'idx_stock_movements_type');
            $table->index('created_at', 'idx_stock_movements_date');
            $table->index(['product_id', 'type'], 'idx_stock_movements_product_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};