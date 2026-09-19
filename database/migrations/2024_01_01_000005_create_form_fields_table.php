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
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('forms')->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('form_sections')->nullOnDelete();
            $table->string('type')->comment('short_text, long_text, email, phone, number, multiple_choice, checkbox, dropdown, date, time, file, rating, linear_scale, checkbox_grid, multiple_choice_grid, section_header, heading, description, image');
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('placeholder')->nullable();
            $table->string('default_value')->nullable();
            $table->string('help_text')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('order_index')->default(0);
            $table->json('validation_rules')->nullable()->comment('min, max, minLength, maxLength, pattern, fileTypes, maxFileSize, etc.');
            $table->json('settings')->nullable()->comment('rows/cols for grid, min/max for linear scale, etc.');
            $table->string('width')->default('full')->comment('full, half, third');
            $table->timestamps();

            $table->index(['form_id', 'order_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};
