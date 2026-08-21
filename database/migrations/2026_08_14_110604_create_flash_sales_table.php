<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : flash_sales
     * 
     * Rôle : Ventes flash avec durée limitée
     * Relation : Many-to-Many avec products (via table pivot)
     */
    public function up(): void
    {
        Schema::create('flash_sales', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 📝 Informations de base
            $table->string('name', 150)->comment("Nom de la vente flash");
            $table->text('description')->nullable()->comment("Description");
            
            // 💰 Remises
            $table->decimal('discount_percentage', 5, 2)->comment("Pourcentage de réduction");
            $table->decimal('maximum_discount', 12, 2)->nullable()->comment("Réduction maximale");
  
            // ✅ NOUVEAU
            $table->timestamp('start_time')->nullable()->comment("Date et heure de début");
            $table->timestamp('end_time')->nullable()->comment("Date et heure de fin");

            // 📊 Limites
            $table->integer('max_items_per_user')->default(1)->comment("Articles maximum par utilisateur");
            $table->integer('total_stock_limit')->nullable()->comment("Stock total disponible pour la vente");
            $table->integer('sold_count')->default(0)->comment("Nombre d'articles vendus");
            
            // 🖼️ Bannière
            $table->string('banner', 255)->nullable()->comment("Bannière de la vente flash");
            
            // ✅ Statut
            $table->boolean('is_active')->default(true)->comment("Vente flash active");
            $table->boolean('is_featured')->default(false)->comment("Mise en avant");
            
            // 📊 Compteur
            $table->boolean('show_countdown')->default(true)->comment("Afficher le compte à rebours");
            
            // ⏰ Horodatage
            $table->timestamps();
            $table->softDeletes();
            
            // 🔍 Index
            $table->index('start_time', 'idx_flash_sales_start');
            $table->index('end_time', 'idx_flash_sales_end');
            $table->index('is_active', 'idx_flash_sales_active');
            $table->index(['is_active', 'start_time', 'end_time'], 'idx_flash_sales_active_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flash_sales');
    }
};