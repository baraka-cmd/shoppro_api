<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : shipping_methods
     * 
     * Rôle : Méthodes de livraison disponibles
     * Relation : One-to-Many avec orders (1 méthode = plusieurs commandes)
     */
    public function up(): void
    {
        Schema::create('shipping_methods', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 📝 Informations de base
            $table->string('name', 150)->comment("Nom de la méthode (ex: Express, Standard)");
            $table->string('code', 50)->unique()->comment("Code unique (ex: express_24h)");
            $table->text('description')->nullable()->comment("Description de la méthode");
            
            // 💰 Tarification
            $table->decimal('base_cost', 12, 2)->default(0)->comment("Coût de base");
            $table->decimal('cost_per_kg', 12, 2)->default(0)->comment("Coût par kilogramme");
            $table->decimal('free_shipping_threshold', 12, 2)->nullable()->comment("Seuil pour livraison gratuite");
            
            // ⏱️ Délais
            $table->integer('min_delivery_days')->default(1)->comment("Délai minimum en jours");
            $table->integer('max_delivery_days')->default(7)->comment("Délai maximum en jours");
            
            // 📍 Zones de livraison
            $table->string('countries', 255)->nullable()->comment("Pays desservis (JSON)");
            $table->string('cities', 255)->nullable()->comment("Villes desservies (JSON)");
            $table->string('excluded_areas', 255)->nullable()->comment("Zones exclues (JSON)");
            
            // 📦 Restrictions
            $table->decimal('max_weight', 10, 2)->nullable()->comment("Poids maximum (kg)");
            $table->decimal('max_dimensions', 10, 2)->nullable()->comment("Dimensions maximum (cm)");
            
            // 🖼️ Icône
            $table->string('icon', 100)->nullable()->comment("Icône de la méthode");
            
            // 📊 Position et statut
            $table->integer('position')->default(0)->comment("Ordre d'affichage");
            $table->boolean('is_active')->default(true)->comment("Méthode active");
            $table->boolean('is_default')->default(false)->comment("Méthode par défaut");
            
            // ⏰ Horodatage
            $table->timestamps();
            $table->softDeletes();
            
            // 🔍 Index
            $table->index('is_active', 'idx_shipping_active');
            $table->index('position', 'idx_shipping_position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_methods');
    }
};