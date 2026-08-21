<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : permission_role (PIVOT)
     * 
     * Rôle : Table pivot pour la relation Many-to-Many entre permissions et roles
     * Relation : permissions (N) ↔ (N) roles
     */
    public function up(): void
    {
        Schema::create('permission_role', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 🔗 Clé étrangère vers permissions
            $table->foreignId('permission_id')
                  ->constrained('permissions')
                  ->onDelete('cascade')
                  ->comment("Permission associée");
            
            // 🔗 Clé étrangère vers roles
            $table->foreignId('role_id')
                  ->constrained('roles')
                  ->onDelete('cascade')
                  ->comment("Rôle associé");
            
            // ⏰ Horodatage
            $table->timestamps();
            
            // 🔍 Contrainte d'unicité (une permission ne peut être donnée qu'une fois à un rôle)
            $table->unique(['permission_id', 'role_id'], 'unique_permission_role');
            
            // 🔍 Index
            $table->index('permission_id', 'idx_permission_role_permission');
            $table->index('role_id', 'idx_permission_role_role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_role');
    }
};