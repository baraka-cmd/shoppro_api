<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : support_tickets
     * 
     * Rôle : Tickets de support client
     * Relations :
     *   - Many-to-One avec users (plusieurs tickets = 1 utilisateur)
     *   - One-to-Many avec ticket_messages (1 ticket = plusieurs messages)
     */
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 📝 Numéro unique du ticket
            $table->string('ticket_number', 50)->unique()->comment("Numéro unique du ticket");
            
            // 🔗 Clé étrangère vers users
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->comment("Utilisateur ayant créé le ticket");
            
            // 🔗 Clé étrangère vers orders (optionnel)
            $table->foreignId('order_id')
                  ->nullable()
                  ->constrained('orders')
                  ->onDelete('set null')
                  ->comment("Commande concernée");
            
            // 📝 Informations du ticket
            $table->string('subject', 255)->comment("Sujet du ticket");
            $table->text('description')->comment("Description du problème");
            
            // 📊 Catégorie et priorité
            $table->enum('category', [
                'order',         // Commande
                'payment',       // Paiement
                'delivery',      // Livraison
                'product',       // Produit
                'account',       // Compte
                'technical',     // Technique
                'other'          // Autre
            ])->default('other')->comment("Catégorie du ticket");
            
            $table->enum('priority', [
                'low',           // Faible
                'medium',        // Moyenne
                'high',          // Haute
                'urgent'         // Urgente
            ])->default('medium')->comment("Priorité du ticket");
            
            // 📊 Statut
            $table->enum('status', [
                'open',          // Ouvert
                'pending',       // En attente
                'resolved',      // Résolu
                'closed',        // Fermé
                'reopened'       // Réouvert
            ])->default('open')->comment("Statut du ticket");
            
            // 👤 Assignation
            $table->foreignId('assigned_to')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null')
                  ->comment("Agent assigné au ticket");
            
            // 📅 Dates
            $table->timestamp('resolved_at')->nullable()->comment("Date de résolution");
            $table->timestamp('closed_at')->nullable()->comment("Date de fermeture");
            $table->timestamp('last_reply_at')->nullable()->comment("Dernière réponse");
            
            // 📊 Satisfaction
            $table->integer('satisfaction_rating')->nullable()->comment("Note de satisfaction (1-5)");
            
            // ⏰ Horodatage
            $table->timestamps();
            $table->softDeletes();
            
            // 🔍 Index
            $table->index('user_id', 'idx_tickets_user');
            $table->index('status', 'idx_tickets_status');
            $table->index('priority', 'idx_tickets_priority');
            $table->index('category', 'idx_tickets_category');
            $table->index('assigned_to', 'idx_tickets_assigned');
            $table->index(['status', 'priority'], 'idx_tickets_status_priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};