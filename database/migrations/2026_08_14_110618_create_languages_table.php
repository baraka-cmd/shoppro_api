<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : languages
     * 
     * Rôle : Langues disponibles dans l'application
     * Relation : Aucune relation directe
     */
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 📝 Informations de base
            $table->string('name', 100)->comment("Nom de la langue (ex: Français)");
            $table->string('code', 10)->unique()->comment("Code ISO (ex: fr, en)");
            $table->string('native_name', 100)->comment("Nom natif (ex: Français, English)");
            
            // 🖼️ Drapeau
            $table->string('flag', 255)->nullable()->comment("Drapeau du pays");
            $table->string('flag_emoji', 10)->nullable()->comment("Emoji drapeau");
            
            // 📊 Direction du texte
            $table->enum('direction', ['ltr', 'rtl'])
                  ->default('ltr')
                  ->comment("Direction du texte (gauche à droite, droite à gauche)");
            
            // ✅ Statut
            $table->boolean('is_default')->default(false)->comment("Langue par défaut");
            $table->boolean('is_active')->default(true)->comment("Langue active");
            
            // ⏰ Horodatage
            $table->timestamps();
            
            // 🔍 Index
            $table->index('is_default', 'idx_languages_default');
            $table->index('is_active', 'idx_languages_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};