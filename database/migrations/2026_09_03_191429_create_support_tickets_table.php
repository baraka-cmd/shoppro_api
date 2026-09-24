<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 50)->unique();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->foreignId('order_id')
                  ->nullable()
                  ->constrained('orders')
                  ->nullOnDelete();
            $table->string('subject', 255);
            $table->enum('category', [
                'general',
                'order',
                'payment',
                'delivery',
                'return',
                'account',
                'technical',
                'other',
            ])->default('general');
            $table->enum('status', [
                'open',
                'in_progress',
                'waiting_user',
                'resolved',
                'closed',
            ])->default('open');
            $table->enum('priority', [
                'low',
                'medium',
                'high',
                'urgent',
            ])->default('medium');
            $table->text('message');
            $table->string('attachment', 255)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index('ticket_number');
            $table->index('user_id');
            $table->index('order_id');
            $table->index('status');
            $table->index('category');
            $table->index('priority');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};