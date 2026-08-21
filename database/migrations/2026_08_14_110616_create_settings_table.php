<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : settings
     * 
     * Rôle : Paramètres généraux de l'application
     * Relation : Aucune relation directe
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 📝 Informations de base
            $table->string('key', 100)->unique()->comment("Clé du paramètre (ex: site_name)");
            $table->text('value')->nullable()->comment("Valeur du paramètre");
            $table->string('group', 100)->nullable()->comment("Groupe de paramètres");
            
            // 📊 Type de valeur
            $table->enum('type', [
                'string',        // Chaîne de caractères
                'integer',       // Nombre entier
                'float',         // Nombre décimal
                'boolean',       // Booléen
                'json',          // JSON
                'array',         // Tableau
                'file'           // Fichier
            ])->default('string')->comment("Type de la valeur");
            
            // 📝 Description
            $table->text('description')->nullable()->comment("Description du paramètre");
            
            // ✅ Statut
            $table->boolean('is_public')->default(false)->comment("Paramètre public (visible côté client)");
            $table->boolean('is_active')->default(true)->comment("Paramètre actif");
            
            // ⏰ Horodatage
            $table->timestamps();
            
            // 🔍 Index
            $table->index('group', 'idx_settings_group');
            $table->index('is_public', 'idx_settings_public');
            $table->index('is_active', 'idx_settings_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};