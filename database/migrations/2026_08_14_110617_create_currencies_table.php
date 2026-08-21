<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : currencies
     * 
     * Rôle : Devises disponibles dans l'application
     * Relation : Aucune relation directe
     */
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 📝 Informations de base
            $table->string('name', 100)->comment("Nom de la devise (ex: Franc CFA)");
            $table->string('code', 10)->unique()->comment("Code ISO (ex: FCFA, USD, EUR)");
            $table->string('symbol', 10)->comment("Symbole (ex: FCFA, $, €)");
            
            // 💰 Taux de change
            $table->decimal('exchange_rate', 15, 6)->default(1)->comment("Taux de change par rapport à la devise de base");
            
            // 📊 Formatage
            $table->string('decimal_separator', 5)->default('.')->comment("Séparateur décimal");
            $table->string('thousand_separator', 5)->default(',')->comment("Séparateur de milliers");
            $table->integer('decimal_places')->default(2)->comment("Nombre de décimales");
            
            // 📊 Position du symbole
            $table->enum('symbol_position', ['before', 'after'])
                  ->default('after')
                  ->comment("Position du symbole");
            
            // ✅ Statut
            $table->boolean('is_default')->default(false)->comment("Devise par défaut");
            $table->boolean('is_active')->default(true)->comment("Devise active");
            
            // ⏰ Horodatage
            $table->timestamps();
            
            // 🔍 Index
            $table->index('is_default', 'idx_currencies_default');
            $table->index('is_active', 'idx_currencies_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};