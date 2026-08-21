<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : refresh_tokens
     * 
     * Rôle : Stocke les refresh tokens pour la gestion de session
     */
    public function up(): void
    {
        Schema::create('refresh_tokens', function (Blueprint $table) {
            $table->id();
            
            // 🔗 Clé étrangère vers users
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->comment("Utilisateur propriétaire du token");
            
            // 🔑 Token
            $table->string('token', 500)->unique()->comment("Refresh token");
            $table->string('device_name', 150)->nullable()->comment("Nom de l'appareil");
            $table->string('device_type', 50)->nullable()->comment("Type d'appareil (mobile, web)");
            $table->string('ip_address', 45)->nullable()->comment("Adresse IP");
            $table->text('user_agent')->nullable()->comment("Navigateur");
            
            // 📅 Dates
            $table->timestamp('expires_at')->comment("Date d'expiration");
            $table->timestamp('last_used_at')->nullable()->comment("Dernière utilisation");
            $table->timestamp('revoked_at')->nullable()->comment("Date de révocation");
            
            // ✅ Statut
            $table->boolean('is_revoked')->default(false)->comment("Token révoqué");
            $table->boolean('is_active')->default(true)->comment("Token actif");
            
            $table->timestamps();
            
            // 🔍 Index
            $table->index('user_id', 'idx_refresh_tokens_user');
            $table->index('is_active', 'idx_refresh_tokens_active');
            $table->index('expires_at', 'idx_refresh_tokens_expiry');
            $table->index(['user_id', 'is_active'], 'idx_refresh_tokens_user_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refresh_tokens');
    }
};