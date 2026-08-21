<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : role_user (PIVOT)
     * 
     * Rôle : Table pivot pour la relation Many-to-Many entre roles et users
     * Relation : roles (N) ↔ (N) users
     */
    public function up(): void
    {
        Schema::create('role_user', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 🔗 Clé étrangère vers roles
            $table->foreignId('role_id')
                  ->constrained('roles')
                  ->onDelete('cascade')
                  ->comment("Rôle attribué");
            
            // 🔗 Clé étrangère vers users
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->comment("Utilisateur associé");
            
            // 📅 Date d'attribution
            $table->timestamp('assigned_at')->useCurrent()->comment("Date d'attribution du rôle");
            
            // 🔗 Attribué par
            $table->foreignId('assigned_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null')
                  ->comment("Utilisateur ayant attribué le rôle");
            
            // ⏰ Horodatage
            $table->timestamps();
            
            // 🔍 Contrainte d'unicité (un utilisateur ne peut avoir qu'une fois le même rôle)
            $table->unique(['role_id', 'user_id'], 'unique_role_user');
            
            // 🔍 Index
            $table->index('role_id', 'idx_role_user_role');
            $table->index('user_id', 'idx_role_user_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
    }
};