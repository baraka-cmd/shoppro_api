<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : roles
     * 
     * Rôle : Rôles des utilisateurs (admin, client, vendeur, etc.)
     * Relation : Many-to-Many avec users (via table pivot role_user)
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 📝 Informations de base
            $table->string('name', 100)->unique()->comment("Nom du rôle (ex: admin, client)");
            $table->string('display_name', 150)->comment("Nom affiché du rôle");
            $table->text('description')->nullable()->comment("Description du rôle");
            
            // 📊 Niveau hiérarchique
            $table->integer('level')->default(1)->comment("Niveau hiérarchique (1 = plus bas)");
            
            // 🎨 Apparence
            $table->string('color', 20)->nullable()->comment("Couleur associée au rôle");
            $table->string('icon', 100)->nullable()->comment("Icône du rôle");
            
            // ✅ Statut
            $table->boolean('is_active')->default(true)->comment("Rôle actif");
            $table->boolean('is_system')->default(false)->comment("Rôle système (ne peut être supprimé)");
            
            // ⏰ Horodatage
            $table->timestamps();
            $table->softDeletes();
            
            // 🔍 Index
            $table->index('level', 'idx_roles_level');
            $table->index('is_active', 'idx_roles_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};