<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : admin_logs
     * 
     * Rôle : Journal des activités des administrateurs
     * Relation : Many-to-One avec users (plusieurs logs = 1 administrateur)
     */
    public function up(): void
    {
        Schema::create('admin_logs', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 🔗 Clé étrangère vers users
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->comment("Administrateur ayant effectué l'action");
            
            // 📝 Informations de l'action
            $table->string('action', 150)->comment("Action effectuée (ex: create_product)");
            $table->string('module', 100)->nullable()->comment("Module concerné (ex: products, orders)");
            $table->text('description')->nullable()->comment("Description détaillée de l'action");
            
            // 🎯 Cible de l'action
            $table->string('target_type', 100)->nullable()->comment("Type de cible (ex: Product)");
            $table->string('target_id', 100)->nullable()->comment("ID de la cible");
            
            // 📊 Données avant/après
            $table->text('old_data')->nullable()->comment("Données avant modification (JSON)");
            $table->text('new_data')->nullable()->comment("Données après modification (JSON)");
            
            // 📍 Informations de la requête
            $table->string('ip_address', 45)->nullable()->comment("Adresse IP de l'administrateur");
            $table->text('user_agent')->nullable()->comment("Navigateur de l'administrateur");
            $table->string('url', 255)->nullable()->comment("URL de la requête");
            $table->string('method', 10)->nullable()->comment("Méthode HTTP (GET, POST, etc.)");
            
            // 📊 Statut
            $table->boolean('is_successful')->default(true)->comment("Action réussie");
            $table->text('error_message')->nullable()->comment("Message d'erreur si échec");
            
            // ⏰ Horodatage
            $table->timestamps();
            
            // 🔍 Index
            $table->index('user_id', 'idx_admin_logs_user');
            $table->index('action', 'idx_admin_logs_action');
            $table->index('module', 'idx_admin_logs_module');
            $table->index('created_at', 'idx_admin_logs_date');
            $table->index(['user_id', 'action'], 'idx_admin_logs_user_action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_logs');
    }
};