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
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faq_category_id')
                  ->nullable()
                  ->constrained('faq_categories')
                  ->nullOnDelete();
            $table->string('question', 500);
            $table->text('answer');
            $table->string('slug', 550)->unique();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->integer('sort_order')->default(0);
            $table->integer('views_count')->default(0);
            $table->integer('helpful_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index('is_active');
            $table->index('is_popular');
            $table->index('sort_order');
            $table->index('faq_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};