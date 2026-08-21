<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : order_trackings
     * 
     * Rôle : Suivi détaillé des expéditions
     * Relations :
     *   - Many-to-One avec orders (plusieurs suivis = 1 commande)
     */
    public function up(): void
    {
        Schema::create('order_trackings', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 🔗 Clé étrangère vers orders
            $table->foreignId('order_id')
                  ->constrained('orders')
                  ->onDelete('cascade')
                  ->comment("Commande suivie");
            
            // 📦 Informations de suivi
            $table->string('tracking_number', 100)->comment("Numéro de suivi du transporteur");
            $table->string('carrier', 100)->nullable()->comment("Transporteur (DHL, FedEx, etc.)");
            $table->string('carrier_url', 255)->nullable()->comment("URL de suivi du transporteur");
            
            // 📊 Statut du suivi
            $table->enum('status', [
                'pending',       // En attente
                'picked_up',     // Ramassé
                'in_transit',    // En transit
                'out_for_delivery', // En cours de livraison
                'delivered',     // Livré
                'failed',        // Échec de livraison
                'returned'       // Retourné
            ])->default('pending')->comment("Statut de livraison");
            
            // 📍 Localisation actuelle
            $table->string('current_location', 255)->nullable()->comment("Localisation actuelle");
            $table->decimal('latitude', 10, 8)->nullable()->comment("Latitude GPS");
            $table->decimal('longitude', 11, 8)->nullable()->comment("Longitude GPS");
            
            // 📅 Dates importantes
            $table->timestamp('estimated_delivery')->nullable()->comment("Date de livraison estimée");
            $table->timestamp('actual_delivery')->nullable()->comment("Date de livraison réelle");
            
            // 📝 Informations du destinataire
            $table->string('recipient_name', 150)->nullable()->comment("Nom du destinataire");
            $table->string('recipient_phone', 20)->nullable()->comment("Téléphone du destinataire");
            
            // 🔄 Historique des événements
            $table->text('events')->nullable()->comment("Historique des événements (JSON)");
            
            // ⏰ Horodatage
            $table->timestamps();
            
            // 🔍 Index
            $table->index('order_id', 'idx_tracking_order');
            $table->index('tracking_number', 'idx_tracking_number');
            $table->index('status', 'idx_tracking_status');
            $table->index('carrier', 'idx_tracking_carrier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_trackings');
    }
};