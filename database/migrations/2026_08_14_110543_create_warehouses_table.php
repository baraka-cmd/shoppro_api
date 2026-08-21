<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : warehouses
     * 
     * Rôle : Entrepôts de stockage des produits
     * Relation : One-to-Many avec stocks (1 entrepôt = plusieurs stocks)
     */
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 📝 Informations de base
            $table->string('name', 150)->comment("Nom de l'entrepôt");
            $table->string('code', 50)->unique()->comment("Code unique de l'entrepôt");
            $table->text('description')->nullable()->comment("Description de l'entrepôt");
            
            // 📍 Localisation
            $table->string('address_line1', 255)->comment("Adresse ligne 1");
            $table->string('address_line2', 255)->nullable()->comment("Adresse ligne 2");
            $table->string('city', 100)->comment("Ville");
            $table->string('state', 100)->nullable()->comment("Région/État");
            $table->string('postal_code', 20)->nullable()->comment("Code postal");
            $table->string('country', 100)->default('Cameroun')->comment("Pays");
            $table->decimal('latitude', 10, 8)->nullable()->comment("Latitude GPS");
            $table->decimal('longitude', 11, 8)->nullable()->comment("Longitude GPS");
            
            // 📞 Contact
            $table->string('phone', 20)->nullable()->comment("Téléphone de l'entrepôt");
            $table->string('email', 150)->nullable()->comment("Email de l'entrepôt");
            $table->string('manager_name', 150)->nullable()->comment("Nom du gestionnaire");
            
            // 📦 Capacité
            $table->integer('capacity')->nullable()->comment("Capacité totale de stockage");
            $table->integer('current_occupancy')->default(0)->comment("Occupation actuelle");
            $table->string('unit', 20)->default('m2')->comment("Unité de mesure (m2, m3)");
            
            // ✅ Statut
            $table->boolean('is_active')->default(true)->comment("Entrepôt actif");
            $table->boolean('is_default')->default(false)->comment("Entrepôt principal");
            
            // ⏰ Horodatage
            $table->timestamps();
            $table->softDeletes();
            
            // 🔍 Index
            $table->index('city', 'idx_warehouses_city');
            $table->index('is_active', 'idx_warehouses_active');
            $table->index('is_default', 'idx_warehouses_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};