<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : newsletter_subscribers
     * 
     * Rôle : Inscriptions à la newsletter
     * Relation : Aucune relation obligatoire (peut être lié à un utilisateur)
     */
    public function up(): void
    {
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 📝 Informations de base
            $table->string('email', 150)->unique()->comment("Email de l'abonné");
            $table->string('name', 150)->nullable()->comment("Nom de l'abonné");
            
            // 🔗 Clé étrangère vers users (optionnel)
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null')
                  ->comment("Utilisateur associé (si inscrit)");
            
            // 📊 Statut
            $table->enum('status', [
                'subscribed',    // Abonné
                'unsubscribed',  // Désabonné
                'bounced',       // Email rejeté
                'spam'          // Signalé comme spam
            ])->default('subscribed')->comment("Statut de l'abonnement");
            
            // 🔑 Token de désabonnement
            $table->string('unsubscribe_token', 100)->unique()->comment("Token de désabonnement");
            
            // 📅 Dates
            $table->timestamp('subscribed_at')->useCurrent()->comment("Date d'inscription");
            $table->timestamp('unsubscribed_at')->nullable()->comment("Date de désabonnement");
            $table->timestamp('last_email_sent_at')->nullable()->comment("Dernier email envoyé");
            
            // 📊 Statistiques
            $table->integer('emails_sent_count')->default(0)->comment("Nombre d'emails envoyés");
            $table->integer('emails_opened_count')->default(0)->comment("Nombre d'emails ouverts");
            $table->integer('emails_clicked_count')->default(0)->comment("Nombre de clics");
            
            // 🎯 Préférences
            $table->string('preferences', 255)->nullable()->comment("Préférences (JSON)");
            $table->string('source', 100)->nullable()->comment("Source d'inscription");
            
            // ⏰ Horodatage
            $table->timestamps();
            
            // 🔍 Index
            $table->index('status', 'idx_newsletter_status');
            $table->index('subscribed_at', 'idx_newsletter_subscribed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
    }
};