<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : permissions
     * 
     * Rôle : Permissions accordées aux rôles
     * Relation : Many-to-Many avec roles (via table pivot permission_role)
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 📝 Informations de base
            $table->string('name', 100)->unique()->comment("Nom de la permission (ex: create-product)");
            $table->string('display_name', 150)->comment("Nom affiché");
            $table->text('description')->nullable()->comment("Description de la permission");
            
            // 📊 Groupe
            $table->string('group', 100)->nullable()->comment("Groupe de permissions (ex: products, orders)");
            $table->string('action', 50)->nullable()->comment("Action (create, read, update, delete)");
            
            // ✅ Statut
            $table->boolean('is_active')->default(true)->comment("Permission active");
            $table->boolean('is_system')->default(false)->comment("Permission système");
            
            // ⏰ Horodatage
            $table->timestamps();
            
            // 🔍 Index
            $table->index('group', 'idx_permissions_group');
            $table->index('action', 'idx_permissions_action');
            $table->index('is_active', 'idx_permissions_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};