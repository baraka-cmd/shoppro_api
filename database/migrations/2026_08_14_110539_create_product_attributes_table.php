<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : product_attributes
     * 
     * Rôle : Attributs dynamiques des produits (couleur, taille, poids, etc.)
     * Relation : Many-to-One avec products (plusieurs attributs = 1 produit)
     */
    public function up(): void
    {
        Schema::create('product_attributes', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 🔗 Clé étrangère vers products
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->onDelete('cascade')
                  ->comment("Produit associé à cet attribut");
            
            // 🏷️ Informations de l'attribut
            $table->string('name', 100)->comment("Nom de l'attribut (ex: Couleur, Taille)");
            $table->string('value', 255)->comment("Valeur de l'attribut (ex: Rouge, XL)");
            
            // 🎨 Options d'affichage
            $table->string('unit', 50)->nullable()->comment("Unité de mesure (kg, cm, etc.)");
            $table->string('icon', 100)->nullable()->comment("Icône de l'attribut");
            $table->string('color_code', 20)->nullable()->comment("Code couleur si attribut visuel");
            
            // 📊 Tri et visibilité
            $table->integer('position')->default(0)->comment("Ordre d'affichage");
            $table->boolean('is_visible')->default(true)->comment("Visible sur la fiche produit");
            $table->boolean('is_filterable')->default(false)->comment("Utilisable comme filtre");
            
            // ✅ Statut
            $table->boolean('is_active')->default(true)->comment("Attribut actif");
            
            // ⏰ Horodatage
            $table->timestamps();
            
            // 🔍 Index
            $table->index('product_id', 'idx_attributes_product');
            $table->index('name', 'idx_attributes_name');
            $table->index('is_filterable', 'idx_attributes_filterable');
            $table->index(['product_id', 'name'], 'idx_attributes_product_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
    }
};