<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : review_votes
     * 
     * Rôle : Votes des utilisateurs sur les avis (utile/pas utile)
     * Relations :
     *   - Many-to-One avec reviews (plusieurs votes = 1 avis)
     *   - Many-to-One avec users (plusieurs votes = 1 utilisateur)
     */
    public function up(): void
    {
        Schema::create('review_votes', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 🔗 Clé étrangère vers reviews
            $table->foreignId('review_id')
                  ->constrained('reviews')
                  ->onDelete('cascade')
                  ->comment("Avis concerné par le vote");
            
            // 🔗 Clé étrangère vers users
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->comment("Utilisateur ayant voté");
            
            // 📊 Type de vote
            $table->enum('vote_type', ['helpful', 'not_helpful'])
                  ->comment("Type de vote");
            
            // 📅 Date du vote
            $table->timestamp('voted_at')->useCurrent()->comment("Date du vote");
            
            // ⏰ Horodatage
            $table->timestamps();
            
            // 🔍 Contrainte d'unicité (un utilisateur ne peut voter qu'une fois par avis)
            $table->unique(['review_id', 'user_id'], 'unique_review_user_vote');
            
            // 🔍 Index
            $table->index('review_id', 'idx_review_votes_review');
            $table->index('user_id', 'idx_review_votes_user');
            $table->index('vote_type', 'idx_review_votes_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_votes');
    }
};