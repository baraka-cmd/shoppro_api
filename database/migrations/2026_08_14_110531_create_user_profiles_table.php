<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : user_profiles
     * 
     * Rôle : Stocke les informations supplémentaires des utilisateurs
     * Relation : One-to-One avec users (1 utilisateur = 1 profil)
     */
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 🔗 Clé étrangère vers users
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->comment('Utilisateur associé');
            
            // 📝 Informations personnelles
            $table->string('first_name')->nullable()->comment('Prénom');
            $table->string('last_name')->nullable()->comment('Nom');
            $table->string('phone', 20)->nullable()->comment('Téléphone');
            $table->string('avatar')->nullable()->comment('Photo de profil');
            $table->date('birth_date')->nullable()->comment('Date de naissance');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->comment('Genre');
            
            // 📍 Localisation
            $table->string('country', 100)->nullable()->comment('Pays');
            $table->string('city', 100)->nullable()->comment('Ville');
            $table->text('address')->nullable()->comment('Adresse principale');
            
            // 🎯 Préférences
            $table->string('language', 10)->default('fr')->comment('Langue préférée');
            $table->string('currency', 10)->default('FCFA')->comment('Devise préférée');
            $table->boolean('newsletter_subscribed')->default(true)->comment('Abonné newsletter');
            $table->boolean('sms_notifications')->default(true)->comment('Notifications SMS');
            $table->boolean('push_notifications')->default(true)->comment('Notifications push');
            
            // 📊 Statistiques
            $table->integer('total_orders')->default(0)->comment('Nombre total de commandes');
            $table->decimal('total_spent', 12, 2)->default(0)->comment('Montant total dépensé');
            
            // ⏰ Horodatage
            $table->timestamps();
            $table->softDeletes()->comment('Suppression douce');
            
            // 🔍 Index pour les recherches fréquentes
            $table->index('phone', 'idx_user_profiles_phone');
            $table->index('city', 'idx_user_profiles_city');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};