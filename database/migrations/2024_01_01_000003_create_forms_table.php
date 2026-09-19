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
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'published', 'unpublished', 'archived'])->default('draft');
            $table->json('settings')->nullable()->comment('Theme, submit button text, confirmation message, etc.');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_multi_section')->default(false);
            $table->string('submit_button_text')->default('Submit');
            $table->text('confirmation_message')->nullable();
            $table->string('redirect_url')->nullable();
            $table->boolean('allow_multiple_responses')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};
