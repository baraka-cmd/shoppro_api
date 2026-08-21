<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : suppliers
     * 
     * Rôle : Fournisseurs des produits
     * Relation : One-to-Many avec products (1 fournisseur = plusieurs produits)
     */
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 📝 Informations de base
            $table->string('name', 150)->comment("Nom du fournisseur");
            $table->string('code', 50)->unique()->comment("Code unique du fournisseur");
            $table->text('description')->nullable()->comment("Description du fournisseur");
            
            // 📞 Contact principal
            $table->string('contact_name', 150)->nullable()->comment("Nom du contact principal");
            $table->string('email', 150)->nullable()->comment("Email du fournisseur");
            $table->string('phone', 20)->nullable()->comment("Téléphone principal");
            $table->string('phone_alt', 20)->nullable()->comment("Téléphone secondaire");
            
            // 📍 Adresse
            $table->string('address_line1', 255)->nullable()->comment("Adresse ligne 1");
            $table->string('address_line2', 255)->nullable()->comment("Adresse ligne 2");
            $table->string('city', 100)->nullable()->comment("Ville");
            $table->string('state', 100)->nullable()->comment("Région/État");
            $table->string('country', 100)->default('Cameroun')->comment("Pays");
            
            // 🏦 Informations bancaires
            $table->string('bank_name', 150)->nullable()->comment("Nom de la banque");
            $table->string('account_number', 50)->nullable()->comment("Numéro de compte");
            $table->string('account_name', 150)->nullable()->comment("Nom sur le compte");
            
            // 📊 Statistiques
            $table->integer('products_count')->default(0)->comment("Nombre de produits fournis");
            $table->decimal('total_purchases', 15, 2)->default(0)->comment("Total des achats");
            $table->integer('orders_count')->default(0)->comment("Nombre de commandes");
            $table->date('last_order_date')->nullable()->comment("Date de la dernière commande");
            
            // 📝 Conditions
            $table->integer('payment_terms')->default(30)->comment("Délai de paiement en jours");
            $table->integer('delivery_time')->default(7)->comment("Délai de livraison en jours");
            $table->decimal('minimum_order', 12, 2)->default(0)->comment("Commande minimum");
            
            // ✅ Statut
            $table->boolean('is_active')->default(true)->comment("Fournisseur actif");
            $table->boolean('is_preferred')->default(false)->comment("Fournisseur préféré");
            
            // ⏰ Horodatage
            $table->timestamps();
            $table->softDeletes();
            
            // 🔍 Index
            $table->index('name', 'idx_suppliers_name');
            $table->index('email', 'idx_suppliers_email');
            $table->index('city', 'idx_suppliers_city');
            $table->index('is_active', 'idx_suppliers_active');
            $table->index('is_preferred', 'idx_suppliers_preferred');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};