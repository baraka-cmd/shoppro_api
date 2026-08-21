<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : payment_methods
     * 
     * Rôle : Méthodes de paiement disponibles dans l'application
     * Relation : One-to-Many avec payments (1 méthode = plusieurs paiements)
     */
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 📝 Informations de base
            $table->string('name', 150)->comment("Nom de la méthode (ex: Carte bancaire, Mobile Money)");
            $table->string('code', 50)->unique()->comment("Code unique (ex: card, mobile_money)");
            $table->text('description')->nullable()->comment("Description de la méthode");
            
            // 🖼️ Icône et logo
            $table->string('icon', 100)->nullable()->comment("Icône de la méthode");
            $table->string('logo', 255)->nullable()->comment("Logo du prestataire");
            
            // 💰 Configuration
            $table->decimal('transaction_fee', 5, 2)->default(0)->comment("Frais de transaction (%)");
            $table->decimal('fixed_fee', 12, 2)->default(0)->comment("Frais fixes");
            $table->decimal('minimum_amount', 12, 2)->nullable()->comment("Montant minimum");
            $table->decimal('maximum_amount', 12, 2)->nullable()->comment("Montant maximum");
            
            // 🌍 Disponibilité
            $table->string('countries', 255)->nullable()->comment("Pays disponibles (JSON)");
            $table->string('currencies', 255)->nullable()->comment("Devises acceptées (JSON)");
            
            // 🔐 Configuration API
            $table->text('api_key')->nullable()->comment("Clé API (chiffrée)");
            $table->text('api_secret')->nullable()->comment("Secret API (chiffré)");
            $table->text('webhook_url')->nullable()->comment("URL de webhook");
            
            // 📊 Position et statut
            $table->integer('position')->default(0)->comment("Ordre d'affichage");
            $table->boolean('is_active')->default(true)->comment("Méthode active");
            $table->boolean('is_default')->default(false)->comment("Méthode par défaut");
            $table->boolean('is_test_mode')->default(false)->comment("Mode test");
            
            // ⏰ Horodatage
            $table->timestamps();
            $table->softDeletes();
            
            // 🔍 Index
            $table->index('is_active', 'idx_payment_methods_active');
            $table->index('position', 'idx_payment_methods_position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};