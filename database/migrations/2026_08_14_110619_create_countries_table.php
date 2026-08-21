<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : countries
     * 
     * Rôle : Pays disponibles pour la livraison
     * Relation : One-to-Many avec cities (1 pays = plusieurs villes)
     */
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 📝 Informations de base
            $table->string('name', 150)->comment("Nom du pays (ex: Cameroun)");
            $table->string('code', 10)->unique()->comment("Code ISO (ex: CM, FR)");
            $table->string('iso3', 10)->nullable()->comment("Code ISO3 (ex: CMR, FRA)");
            $table->string('phone_code', 10)->nullable()->comment("Indicatif téléphonique (ex: +237)");
            
            // 🖼️ Drapeau
            $table->string('flag', 255)->nullable()->comment("Drapeau du pays");
            $table->string('flag_emoji', 10)->nullable()->comment("Emoji drapeau");
            
            // 📊 Devise et langue
            $table->string('currency_code', 10)->nullable()->comment("Devise principale");
            $table->string('currency_name', 100)->nullable()->comment("Nom de la devise");
            $table->string('currency_symbol', 10)->nullable()->comment("Symbole de la devise");
            $table->string('language_code', 10)->nullable()->comment("Langue principale");
            
            // 📦 Livraison
            $table->boolean('is_delivery_available')->default(true)->comment("Livraison disponible");
            $table->decimal('delivery_fee', 12, 2)->default(0)->comment("Frais de livraison de base");
            $table->integer('delivery_days')->default(7)->comment("Délai de livraison en jours");
            
            // 📍 Coordonnées
            $table->decimal('latitude', 10, 8)->nullable()->comment("Latitude du centre");
            $table->decimal('longitude', 11, 8)->nullable()->comment("Longitude du centre");
            
            // ✅ Statut
            $table->boolean('is_active')->default(true)->comment("Pays actif");
            $table->boolean('is_default')->default(false)->comment("Pays par défaut");
            
            // ⏰ Horodatage
            $table->timestamps();
            $table->softDeletes();
            
            // 🔍 Index
            $table->index('code', 'idx_countries_code');
            $table->index('is_active', 'idx_countries_active');
            $table->index('is_default', 'idx_countries_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};