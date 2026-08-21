<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : notifications
     * 
     * Rôle : Notifications des utilisateurs (push, in-app, email)
     * Relation : Many-to-One avec users (plusieurs notifications = 1 utilisateur)
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 🔗 Clé étrangère vers users
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->comment("Utilisateur destinataire");
            
            // 📝 Contenu de la notification
            $table->string('title', 255)->comment("Titre de la notification");
            $table->text('body')->comment("Contenu de la notification");
            $table->string('image', 255)->nullable()->comment("Image de la notification");
            $table->string('icon', 100)->nullable()->comment("Icône de la notification");
            
            // 📊 Type de notification
            $table->enum('type', [
                'order',         // Commande
                'promotion',     // Promotion
                'price_alert',   // Alerte prix
                'stock_alert',   // Alerte stock
                'system',        // Système
                'chat',          // Message
                'review'         // Avis
            ])->comment("Type de notification");
            
            // 🔗 Données liées
            $table->string('target_type', 100)->nullable()->comment("Type de cible (order, product)");
            $table->string('target_id', 100)->nullable()->comment("ID de la cible");
            
            // 📊 Statut
            $table->boolean('is_read')->default(false)->comment("Notification lue");
            $table->timestamp('read_at')->nullable()->comment("Date de lecture");
            
            // 📤 Canaux d'envoi
            $table->boolean('sent_push')->default(false)->comment("Envoyée en push");
            $table->boolean('sent_email')->default(false)->comment("Envoyée par email");
            $table->boolean('sent_sms')->default(false)->comment("Envoyée par SMS");
            
            // 📅 Dates
            $table->timestamp('sent_at')->nullable()->comment("Date d'envoi");
            $table->timestamp('received_at')->nullable()->comment("Date de réception");
            
            // ✅ Statut
            $table->boolean('is_active')->default(true)->comment("Notification active");
            
            // ⏰ Horodatage
            $table->timestamps();
            $table->softDeletes();
            
            // 🔍 Index
            $table->index('user_id', 'idx_notifications_user');
            $table->index('type', 'idx_notifications_type');
            $table->index('is_read', 'idx_notifications_read');
            $table->index('created_at', 'idx_notifications_date');
            $table->index(['user_id', 'is_read'], 'idx_notifications_user_read');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};