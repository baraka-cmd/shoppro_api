<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : user_addresses
     * 
     * Rôle : Gère les adresses de livraison multiples des utilisateurs
     * Relation : One-to-Many avec users (1 utilisateur = plusieurs adresses)
     */
    public function up(): void
    {
        Schema::create('user_addresses', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 🔗 Clé étrangère vers users
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->comment('Utilisateur propriétaire');
            
            // 👤 Informations du destinataire
            $table->string('full_name', 150)->comment('Nom complet du destinataire');
            $table->string('phone', 20)->comment('Téléphone du destinataire');
            $table->string('email', 150)->nullable()->comment('Email du destinataire');
            
            // 📍 Adresse détaillée
            $table->string('address_line1', 255)->comment('Adresse ligne 1');
            $table->string('address_line2', 255)->nullable()->comment('Adresse ligne 2 (complément)');
            $table->string('city', 100)->comment('Ville');
            $table->string('state', 100)->nullable()->comment('Région/État');
            $table->string('postal_code', 20)->nullable()->comment('Code postal');
            $table->string('country', 100)->default('Cameroun')->comment('Pays');
            
            // 🏷️ Type d'adresse
            $table->enum('type', ['home', 'work', 'other'])
                  ->default('home')
                  ->comment('Type : domicile, travail, autre');
            
            // ⭐ Adresse par défaut
            $table->boolean('is_default')->default(false)->comment('Adresse principale');
            
            // 📌 Géolocalisation (pour la livraison)
            $table->decimal('latitude', 10, 8)->nullable()->comment('Latitude GPS');
            $table->decimal('longitude', 11, 8)->nullable()->comment('Longitude GPS');
            
            // 📝 Instructions de livraison
            $table->text('delivery_instructions')->nullable()->comment('Instructions spéciales');
            
            // ✅ Statut
            $table->boolean('is_active')->default(true)->comment('Adresse active');
            
            // ⏰ Horodatage
            $table->timestamps();
            $table->softDeletes();
            
            // 🔍 Index pour la recherche
            $table->index('city', 'idx_user_addresses_city');
            $table->index('is_default', 'idx_user_addresses_default');
            $table->index(['user_id', 'is_default'], 'idx_user_default_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};