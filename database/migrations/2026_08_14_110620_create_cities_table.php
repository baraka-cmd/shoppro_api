<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : cities
     * 
     * Rôle : Villes disponibles pour la livraison
     * Relation : Many-to-One avec countries (plusieurs villes = 1 pays)
     */
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 🔗 Clé étrangère vers countries
            $table->foreignId('country_id')
                  ->constrained('countries')
                  ->onDelete('cascade')
                  ->comment("Pays de la ville");
            
            // 📝 Informations de base
            $table->string('name', 150)->comment("Nom de la ville (ex: Douala)");
            $table->string('code', 50)->nullable()->comment("Code de la ville");
            $table->string('postal_code', 20)->nullable()->comment("Code postal");
            
            // 📍 Coordonnées
            $table->decimal('latitude', 10, 8)->nullable()->comment("Latitude GPS");
            $table->decimal('longitude', 11, 8)->nullable()->comment("Longitude GPS");
            
            // 📦 Livraison
            $table->boolean('is_delivery_available')->default(true)->comment("Livraison disponible");
            $table->decimal('delivery_fee', 12, 2)->default(0)->comment("Frais de livraison spécifiques");
            $table->integer('delivery_days')->nullable()->comment("Délai de livraison spécifique");
            $table->decimal('minimum_order', 12, 2)->default(0)->comment("Commande minimum pour livraison");
            
            // 📊 Population
            $table->integer('population')->nullable()->comment("Population de la ville");
            
            // ✅ Statut
            $table->boolean('is_active')->default(true)->comment("Ville active");
            $table->boolean('is_popular')->default(false)->comment("Ville populaire");
            
            // ⏰ Horodatage
            $table->timestamps();
            $table->softDeletes();
            
            // 🔍 Contrainte d'unicité (une ville ne peut exister qu'une fois par pays)
            $table->unique(['country_id', 'name'], 'unique_country_city');
            
            // 🔍 Index
            $table->index('country_id', 'idx_cities_country');
            $table->index('name', 'idx_cities_name');
            $table->index('is_active', 'idx_cities_active');
            $table->index('is_popular', 'idx_cities_popular');
            $table->index(['country_id', 'is_active'], 'idx_cities_country_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};