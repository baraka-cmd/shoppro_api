<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : order_status_histories
     * 
     * Rôle : Historique des changements de statut des commandes
     * Relations :
     *   - Many-to-One avec orders (plusieurs historiques = 1 commande)
     *   - Many-to-One avec users (plusieurs historiques = 1 utilisateur)
     */
    public function up(): void
    {
        Schema::create('order_status_histories', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 🔗 Clé étrangère vers orders
            $table->foreignId('order_id')
                  ->constrained('orders')
                  ->onDelete('cascade')
                  ->comment("Commande concernée");
            
            // 🔗 Clé étrangère vers users (qui a changé le statut)
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null')
                  ->comment("Utilisateur ayant modifié le statut");
            
            // 📊 Statuts
            $table->enum('old_status', [
                'pending', 'confirmed', 'processing', 
                'shipped', 'delivered', 'cancelled', 
                'refunded', 'returned'
            ])->nullable()->comment("Ancien statut");
            
            $table->enum('new_status', [
                'pending', 'confirmed', 'processing', 
                'shipped', 'delivered', 'cancelled', 
                'refunded', 'returned'
            ])->comment("Nouveau statut");
            
            // 📝 Notes
            $table->text('note')->nullable()->comment("Note sur le changement");
            $table->string('reason', 255)->nullable()->comment("Raison du changement");
            
            // 📍 Localisation (pour le suivi)
            $table->string('location', 255)->nullable()->comment("Localisation au moment du changement");
            
            // ⏰ Horodatage
            $table->timestamps();
            
            // 🔍 Index
            $table->index('order_id', 'idx_status_history_order');
            $table->index('new_status', 'idx_status_history_new');
            $table->index('created_at', 'idx_status_history_date');
            $table->index(['order_id', 'created_at'], 'idx_status_history_order_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
    }
};