<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : product_specifications
     * 
     * Rôle : Spécifications techniques détaillées des produits
     * Relation : Many-to-One avec products (plusieurs spécifications = 1 produit)
     */
    public function up(): void
    {
        Schema::create('product_specifications', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 🔗 Clé étrangère vers products
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->onDelete('cascade')
                  ->comment("Produit associé à cette spécification");
            
            // 🏷️ Informations de la spécification
            $table->string('label', 150)->comment("Libellé (ex: Processeur, RAM, Écran)");
            $table->string('value', 255)->comment("Valeur (ex: Intel i7, 16GB, 15 pouces)");
            
            // 📊 Catégorisation
            $table->string('group', 100)->nullable()->comment("Groupe (ex: Performance, Affichage, Batterie)");
            $table->integer('position')->default(0)->comment("Ordre d'affichage dans le groupe");
            
            // 🎨 Affichage
            $table->string('icon', 100)->nullable()->comment("Icône de la spécification");
            $table->boolean('is_highlighted')->default(false)->comment("Mise en avant sur la fiche");
            
            // ✅ Statut
            $table->boolean('is_active')->default(true)->comment("Spécification active");
            
            // ⏰ Horodatage
            $table->timestamps();
            
            // 🔍 Index
            $table->index('product_id', 'idx_specs_product');
            $table->index('group', 'idx_specs_group');
            $table->index(['product_id', 'group'], 'idx_specs_product_group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_specifications');
    }
};