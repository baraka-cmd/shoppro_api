<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : promotions
     * 
     * Rôle : Promotions et offres spéciales sur les produits
     * Relation : Many-to-Many avec products (via table pivot)
     */
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 📝 Informations de base
            $table->string('name', 150)->comment("Nom de la promotion");
            $table->string('slug', 180)->unique()->comment("Slug URL unique");
            $table->text('description')->nullable()->comment("Description de la promotion");
            
            // 💰 Type de promotion
            $table->enum('type', [
                'discount',      // Réduction directe
                'bogo',          // Achetez-en un, obtenez-en un
                'bundle',        // Offre groupée
                'flash_sale',    // Vente flash
                'clearance'      // Liquidation
            ])->comment("Type de promotion");
            
            // 💰 Valeur de la promotion
            $table->decimal('discount_value', 12, 2)->comment("Valeur de la réduction");
            $table->enum('discount_type', ['percentage', 'fixed'])
                  ->default('percentage')
                  ->comment("Type de réduction");
            
            // 📅 Dates
            $table->date('start_date')->comment("Date de début");
            $table->date('end_date')->comment("Date de fin");
            
            // 🖼️ Bannière
            $table->string('banner', 255)->nullable()->comment("Bannière de la promotion");
            $table->string('banner_mobile', 255)->nullable()->comment("Bannière mobile");
            
            // 📊 Conditions
            $table->decimal('minimum_order', 12, 2)->default(0)->comment("Commande minimum");
            $table->integer('usage_limit')->nullable()->comment("Nombre maximum d'utilisations");
            $table->integer('usage_count')->default(0)->comment("Nombre d'utilisations actuel");
            
            // ✅ Statut
            $table->boolean('is_active')->default(true)->comment("Promotion active");
            $table->boolean('is_featured')->default(false)->comment("Mise en avant");
            
            // ⏰ Horodatage
            $table->timestamps();
            $table->softDeletes();
            
            // 🔍 Index
            $table->index('type', 'idx_promotions_type');
            $table->index('is_active', 'idx_promotions_active');
            $table->index('start_date', 'idx_promotions_start');
            $table->index('end_date', 'idx_promotions_end');
            $table->index(['is_active', 'start_date', 'end_date'], 'idx_promotions_active_dates');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};