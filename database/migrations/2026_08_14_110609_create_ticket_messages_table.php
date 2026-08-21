<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table : ticket_messages
     * 
     * Rôle : Messages échangés dans les tickets de support
     * Relations :
     *   - Many-to-One avec support_tickets (plusieurs messages = 1 ticket)
     *   - Many-to-One avec users (plusieurs messages = 1 utilisateur)
     */
    public function up(): void
    {
        Schema::create('ticket_messages', function (Blueprint $table) {
            // 🔑 Clé primaire
            $table->id();
            
            // 🔗 Clé étrangère vers support_tickets
            $table->foreignId('ticket_id')
                  ->constrained('support_tickets')
                  ->onDelete('cascade')
                  ->comment("Ticket concerné");
            
            // 🔗 Clé étrangère vers users
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->comment("Auteur du message");
            
            // 📝 Contenu
            $table->text('message')->comment("Contenu du message");
            
            // 📎 Pièces jointes
            $table->string('attachment', 255)->nullable()->comment("Pièce jointe");
            $table->string('attachment_name', 255)->nullable()->comment("Nom de la pièce jointe");
            $table->integer('attachment_size')->nullable()->comment("Taille de la pièce jointe");
            
            // 📊 Type de message
            $table->enum('sender_type', ['user', 'admin', 'system'])
                  ->default('user')
                  ->comment("Type d'expéditeur");
            
            // 📊 Statut
            $table->boolean('is_read')->default(false)->comment("Message lu");
            $table->timestamp('read_at')->nullable()->comment("Date de lecture");
            
            // ⏰ Horodatage
            $table->timestamps();
            
            // 🔍 Index
            $table->index('ticket_id', 'idx_ticket_messages_ticket');
            $table->index('user_id', 'idx_ticket_messages_user');
            $table->index('is_read', 'idx_ticket_messages_read');
            $table->index(['ticket_id', 'created_at'], 'idx_ticket_messages_ticket_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_messages');
    }
};